<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Auth\Enums\OrganizationRole;
use App\Livewire\Onboarding\OrganizationCreate;
use App\Livewire\Onboarding\PlanSelect;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('prevents duplicate organization slug during onboarding', function (): void {
    Organization::factory()->create(['slug' => 'existing-slug']);

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::AccountCreated,
    ]);

    $this->actingAs($user);

    Livewire::test(OrganizationCreate::class)
        ->set('name', 'Another Org')
        ->set('slug', 'existing-slug')
        ->set('country', 'NL')
        ->set('billing_email', 'billing@another.test')
        ->set('timezone', 'Europe/Amsterdam')
        ->set('locale', 'nl')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('shows plan options from billing config catalog in onboarding page', function (): void {
    $organization = Organization::factory()->create();

    $user = User::factory()->create([
        'onboarding_status' => OnboardingStatus::OrganizationCreated,
    ]);

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    config()->set('billing.plans.starter.name', 'Starter');
    config()->set('billing.plans.pro.name', 'Pro');
    config()->set('billing.plans.enterprise.name', 'Enterprise');

    Livewire::actingAs($user)
        ->test(PlanSelect::class)
        ->assertSee('Starter')
        ->assertSee('Pro')
        ->assertSee('Enterprise');
});
