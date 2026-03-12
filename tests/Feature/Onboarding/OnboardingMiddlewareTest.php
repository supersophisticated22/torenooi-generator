<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\User;

it('redirects users without an organization to onboarding organization step', function (): void {
    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::AccountCreated,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.organization'));
});

it('redirects users with organization but no plan to plan selection', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => null,
        'subscription_status' => null,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::OrganizationCreated,
    ]);

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.plan'));
});

it('redirects users with plan but no active subscription to payment step', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_status' => null,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::PlanSelected,
    ]);

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.payment'));
});

it('allows dashboard for fully onboarded subscribed users', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Pro,
        'subscription_plan' => BillingPlan::Pro,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::OnboardingComplete,
    ]);

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('does not bypass onboarding when status is complete but organization is missing', function (): void {
    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::OnboardingComplete,
        'current_organization_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.organization'));
});

it('redirects onboarding index to the correct current step', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_status' => null,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::CheckoutPending,
    ]);

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('onboarding.index'))
        ->assertRedirect(route('onboarding.payment'));
});
