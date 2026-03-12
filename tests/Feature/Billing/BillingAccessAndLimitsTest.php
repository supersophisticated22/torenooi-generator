<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Teams\Create as TeamCreate;
use App\Livewire\Tournaments\Create as TournamentCreate;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Livewire\Livewire;

it('blocks non admin users from billing area', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);
    $user = User::factory()->create();

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::Viewer->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('billing.show'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('billing.portal'))
        ->assertForbidden();
});

it('enforces starter team limit in backend creation flow', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
    ]);

    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'starter-limit-sport',
    ]);

    Team::factory()->count(20)->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(TeamCreate::class)
        ->call('save')
        ->assertHasErrors(['plan'])
        ->assertSee('Upgrade plan')
        ->assertDontSee('Please contact your organization admin to upgrade the plan.');
});

it('enforces starter tournament limit in backend creation flow', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
    ]);

    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'starter-limit-tournament-sport',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Tournament::factory()->count(3)->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Draft,
    ]);

    $this->actingAs($user);

    Livewire::test(TournamentCreate::class)
        ->call('save')
        ->assertHasErrors(['plan'])
        ->assertSee('Upgrade plan')
        ->assertDontSee('Please contact your organization admin to upgrade the plan.');
});

it('shows contact-admin guidance for event managers when tournament limit is reached', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
    ]);

    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::EventManager->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'starter-limit-tournament-sport-event-manager',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Tournament::factory()->count(3)->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Draft,
    ]);

    $this->actingAs($user);

    Livewire::test(TournamentCreate::class)
        ->call('save')
        ->assertHasErrors(['plan'])
        ->assertSee('Please contact your organization admin to upgrade the plan.')
        ->assertDontSee('Upgrade plan');
});

it('activates free plan locally without Stripe checkout for organization admins', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Pro,
        'subscription_plan' => BillingPlan::Pro,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->post(route('billing.checkout', ['plan' => BillingPlan::Free->value]))
        ->assertRedirect(route('billing.show'));

    $organization->refresh();
    expect($organization->subscription_plan)->toBe(BillingPlan::Free)
        ->and($organization->subscription_status)->toBe(SubscriptionStatus::Active);
});
