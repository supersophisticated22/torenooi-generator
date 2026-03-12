<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Livewire\Organization\Users as OrganizationUsers;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('allows organization admins on paid plans to access user management', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $admin = User::factory()->create();
    $admin->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $admin->update(['current_organization_id' => $organization->id]);

    $this->actingAs($admin)
        ->get(route('organization.users'))
        ->assertOk()
        ->assertSee('Organization Users');
});

it('blocks organization user management on free plan', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Free,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $admin = User::factory()->create();
    $admin->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $admin->update(['current_organization_id' => $organization->id]);

    $this->actingAs($admin)
        ->get(route('organization.users'))
        ->assertRedirect(route('billing.show'));
});

it('blocks non admins even on paid plans', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $viewer = User::factory()->create();
    $viewer->organizations()->attach($organization->id, ['role' => OrganizationRole::Viewer->value]);
    $viewer->update(['current_organization_id' => $organization->id]);

    $this->actingAs($viewer)
        ->get(route('organization.users'))
        ->assertForbidden();
});

it('allows paid organization admins to add and update users in their organization', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $admin = User::factory()->create();
    $admin->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $admin->update(['current_organization_id' => $organization->id]);

    $existingUser = User::factory()->create(['email' => 'member@test.local']);

    Livewire::actingAs($admin)
        ->test(OrganizationUsers::class)
        ->set('email', 'member@test.local')
        ->set('role', OrganizationRole::EventManager->value)
        ->call('saveUser')
        ->assertHasNoErrors();

    expect($organization->users()->where('users.id', $existingUser->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(OrganizationUsers::class)
        ->set('name', 'New Referee')
        ->set('email', 'new-ref@test.local')
        ->set('password', 'new-password-123')
        ->set('role', OrganizationRole::Referee->value)
        ->call('saveUser')
        ->assertHasNoErrors();

    $newUser = User::query()->where('email', 'new-ref@test.local')->first();

    expect($newUser)->not->toBeNull()
        ->and($organization->users()->where('users.id', $newUser->id)->exists())->toBeTrue();
});

it('prevents admins from downgrading their own role', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $admin = User::factory()->create(['email' => 'admin@self-lock.test']);
    $admin->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $admin->update(['current_organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(OrganizationUsers::class)
        ->call('updateRole', $admin->id, OrganizationRole::Viewer->value)
        ->assertHasErrors(['role']);

    expect($admin->fresh()->organizationRole($organization->id))->toBe(OrganizationRole::OrganizationAdmin);

    Livewire::actingAs($admin)
        ->test(OrganizationUsers::class)
        ->set('email', 'admin@self-lock.test')
        ->set('role', OrganizationRole::Viewer->value)
        ->call('saveUser')
        ->assertHasErrors(['role']);

    expect($admin->fresh()->organizationRole($organization->id))->toBe(OrganizationRole::OrganizationAdmin);
});

it('prevents admins from removing themselves from the organization', function (): void {
    $organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $admin = User::factory()->create();
    $admin->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $admin->update(['current_organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(OrganizationUsers::class)
        ->call('removeUser', $admin->id)
        ->assertHasErrors(['remove']);

    expect($organization->users()->where('users.id', $admin->id)->exists())->toBeTrue();
});
