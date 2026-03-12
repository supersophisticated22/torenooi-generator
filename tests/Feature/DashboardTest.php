<?php

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows first run empty state suggestions', function () {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);
    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No sports yet')
        ->assertSee('No teams yet')
        ->assertSee('No events yet')
        ->assertSee('No tournaments yet');
});
