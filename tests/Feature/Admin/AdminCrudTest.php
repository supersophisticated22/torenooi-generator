<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Livewire\Admin\AdminUsers\Create as AdminUsersCreate;
use App\Livewire\Admin\AdminUsers\Index as AdminUsersIndex;
use App\Livewire\Admin\Organizations\Create as OrganizationsCreate;
use App\Livewire\Admin\Organizations\Edit as OrganizationsEdit;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('shows only admin navigation for platform admins', function (): void {
    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertSee('Organizations')
        ->assertSee('Admin Users')
        ->assertDontSee('Sports')
        ->assertDontSee('Teams');
});

it('blocks non platform users from admin pages', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.organizations.index'))
        ->assertForbidden();
});

it('redirects platform admins away from tenant routes', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    $admin->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $admin->update(['current_organization_id' => $organization->id]);

    $this->actingAs($admin)
        ->get(route('sports.index'))
        ->assertRedirect(route('admin.organizations.index'));
});

it('can create and update organizations with disable support', function (): void {
    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(OrganizationsCreate::class)
        ->set('name', 'Platform Managed Org')
        ->set('slug', 'platform-managed-org')
        ->set('billing_email', 'billing@platform.test')
        ->set('country', 'NL')
        ->set('timezone', 'Europe/Amsterdam')
        ->set('locale', 'nl')
        ->set('subscription_plan', BillingPlan::Pro->value)
        ->set('subscription_status', SubscriptionStatus::Active->value)
        ->call('save')
        ->assertHasNoErrors();

    $organization = Organization::query()->where('slug', 'platform-managed-org')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(OrganizationsEdit::class, ['organization' => $organization])
        ->set('name', 'Platform Managed Org Updated')
        ->set('is_disabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->name)->toBe('Platform Managed Org Updated')
        ->and($organization->fresh()->disabled_at)->not->toBeNull();
});

it('can create and manage regular users including memberships and disable state', function (): void {
    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);
    $organization = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test(UsersCreate::class)
        ->set('name', 'Managed User')
        ->set('email', 'managed-user@test.local')
        ->set('password', 'managed-password-123')
        ->set('organization_id', $organization->id)
        ->set('role', OrganizationRole::Viewer->value)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'managed-user@test.local')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(UsersEdit::class, ['user' => $user])
        ->set('name', 'Managed User Updated')
        ->set('is_disabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Managed User Updated')
        ->and($user->fresh()->disabled_at)->not->toBeNull();
});

it('manages platform admins only through dedicated admin user pages', function (): void {
    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(AdminUsersCreate::class)
        ->set('name', 'Second Admin')
        ->set('email', 'second-admin@test.local')
        ->set('password', 'admin-password-123')
        ->call('save')
        ->assertHasNoErrors();

    $secondAdmin = User::query()->where('email', 'second-admin@test.local')->firstOrFail();

    expect($secondAdmin->isPlatformAdmin())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(AdminUsersIndex::class)
        ->call('demoteAdmin', $secondAdmin->id)
        ->assertHasNoErrors();

    expect($secondAdmin->fresh()->isPlatformAdmin())->toBeFalse();
});

it('prevents self lockout by disabling last active platform admin', function (): void {
    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(AdminUsersIndex::class)
        ->call('disableAdmin', $admin->id)
        ->assertHasErrors(['disable']);

    expect($admin->fresh()->disabled_at)->toBeNull();
});

it('keeps impersonation working for tenant access', function (): void {
    $organization = Organization::factory()->create([
        'selected_plan' => BillingPlan::Starter,
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $platformAdmin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    $target = User::factory()->create();
    $target->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $target->update(['current_organization_id' => $organization->id]);

    $this->actingAs($platformAdmin)
        ->post(route('admin.impersonation.start', $target))
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertOk();

    $this->post(route('admin.impersonation.stop'))
        ->assertRedirect(route('admin.organizations.index'));
});
