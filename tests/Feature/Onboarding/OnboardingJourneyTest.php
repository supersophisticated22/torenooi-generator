<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Billing\Actions\CreateCheckoutSession;
use App\Domain\Billing\DTOs\CheckoutSessionData;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Livewire\Onboarding\OrganizationCreate;
use App\Livewire\Onboarding\PlanSelect;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('guides a new user through organization setup plan selection checkout and dashboard access', function (): void {
    $this->post(route('register.store'), [
        'name' => 'New Organizer',
        'email' => 'new-organizer@test.local',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'new-organizer@test.local')->firstOrFail();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('onboarding.organization'));

    Livewire::test(OrganizationCreate::class)
        ->set('name', 'Brussels Schools Cup')
        ->set('slug', 'brussels-schools-cup')
        ->set('country', 'BE')
        ->set('billing_email', 'billing@schools.test')
        ->set('timezone', 'Europe/Brussels')
        ->set('locale', 'nl')
        ->call('save')
        ->assertRedirect(route('onboarding.plan', absolute: false));

    $organization = Organization::query()->where('slug', 'brussels-schools-cup')->firstOrFail();

    $user->refresh();
    expect($user->current_organization_id)->toBe($organization->id)
        ->and($user->onboarding_status)->toBe(OnboardingStatus::OrganizationCreated);

    Livewire::actingAs($user)
        ->test(PlanSelect::class)
        ->call('selectPlan', 'starter')
        ->assertRedirect(route('onboarding.payment', absolute: false));

    expect($organization->fresh()->selected_plan)->toBe(BillingPlan::Starter)
        ->and($user->fresh()->onboarding_status)->toBe(OnboardingStatus::PlanSelected);

    $checkoutAction = Mockery::mock(CreateCheckoutSession::class);
    $checkoutAction->shouldReceive('__invoke')->once()->andReturn(new CheckoutSessionData(
        id: 'cs_onboarding_123',
        url: 'https://checkout.stripe.test/cs_onboarding_123',
    ));
    app()->instance(CreateCheckoutSession::class, $checkoutAction);

    $this->post(route('onboarding.checkout.start'))
        ->assertRedirect('https://checkout.stripe.test/cs_onboarding_123');

    expect($user->fresh()->onboarding_status)->toBe(OnboardingStatus::CheckoutPending);

    $this->get(route('onboarding.checkout.success'))
        ->assertRedirect(route('onboarding.payment'));

    $organization->update([
        'subscription_status' => SubscriptionStatus::Active,
        'subscription_plan' => BillingPlan::Starter,
    ]);

    $this->get(route('onboarding.checkout.success'))
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Brussels Schools Cup')
        ->assertSee('Create sport')
        ->assertSee('Create tournament');

    expect($user->fresh()->onboarding_status)->toBe(OnboardingStatus::OnboardingComplete);
});

it('returns users to plan selection when checkout is canceled', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Pro,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::CheckoutPending,
    ]);

    $user->organizations()->attach($organization->id, ['role' => 'organization_admin']);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('onboarding.checkout.cancel'))
        ->assertRedirect(route('onboarding.plan'));

    expect($user->fresh()->onboarding_status)->toBe(OnboardingStatus::PlanSelected);
});

it('keeps user in payment onboarding step while webhook confirmation is delayed', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_status' => null,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::CheckoutPending,
    ]);

    $user->organizations()->attach($organization->id, ['role' => 'organization_admin']);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.payment'));

    $this->get(route('onboarding.checkout.success'))
        ->assertRedirect(route('onboarding.payment'));
});

it('activates free plan onboarding without starting Stripe checkout', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Free,
        'subscription_status' => null,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::PlanSelected,
    ]);

    $user->organizations()->attach($organization->id, ['role' => 'organization_admin']);
    $user->update(['current_organization_id' => $organization->id]);

    $checkoutAction = Mockery::mock(CreateCheckoutSession::class);
    $checkoutAction->shouldNotReceive('__invoke');
    app()->instance(CreateCheckoutSession::class, $checkoutAction);

    $this->actingAs($user)
        ->post(route('onboarding.checkout.start'))
        ->assertRedirect(route('dashboard'));

    $organization->refresh();
    expect($organization->subscription_plan)->toBe(BillingPlan::Free)
        ->and($organization->subscription_status)->toBe(SubscriptionStatus::Active)
        ->and($user->fresh()->onboarding_status)->toBe(OnboardingStatus::OnboardingComplete);
});
