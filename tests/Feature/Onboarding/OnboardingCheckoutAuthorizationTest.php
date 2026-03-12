<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Models\Organization;
use App\Models\User;

it('blocks non admin users from starting onboarding checkout', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
    ]);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::PlanSelected,
    ]);

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::Viewer->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->post(route('onboarding.checkout.start'))
        ->assertForbidden();
});
