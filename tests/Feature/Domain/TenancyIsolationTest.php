<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Sport;
use App\Models\User;
use App\Tenancy\CurrentOrganization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

it('allows users to belong to multiple organizations and resolve current organization', function (): void {
    $user = User::factory()->create();
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $user->organizations()->attach([$organizationA->id, $organizationB->id]);
    $user->update(['current_organization_id' => $organizationB->id]);

    expect($user->currentOrganization()?->is($organizationB))->toBeTrue()
        ->and($user->belongsToOrganizationId($organizationA->id))->toBeTrue()
        ->and($user->belongsToOrganizationId($organizationB->id))->toBeTrue();
});

it('resolves tenancy context in middleware for authenticated users', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach($organization->id);
    $user->update(['current_organization_id' => $organization->id]);

    Route::middleware(['web', 'auth', 'organization'])
        ->get('/testing/current-organization', function (CurrentOrganization $currentOrganization) {
            return response()->json([
                'organization_id' => $currentOrganization->id(),
            ]);
        });

    $response = $this->actingAs($user)->get('/testing/current-organization');

    $response->assertOk()->assertJson([
        'organization_id' => $organization->id,
    ]);
});

it('supports filtering tenant owned models with organization scopes', function (): void {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    Sport::factory()->create([
        'organization_id' => $organizationA->id,
        'slug' => 'football-a',
    ]);

    Sport::factory()->create([
        'organization_id' => $organizationB->id,
        'slug' => 'football-b',
    ]);

    $sports = Sport::query()->forOrganization($organizationA)->get();

    expect($sports)->toHaveCount(1)
        ->and($sports->first()->organization_id)->toBe($organizationA->id);
});

it('authorizes tenant record management only for the users own organization', function (): void {
    $user = User::factory()->create();
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $user->organizations()->attach($organizationA->id);
    $user->update(['current_organization_id' => $organizationA->id]);

    $ownSport = Sport::factory()->create([
        'organization_id' => $organizationA->id,
        'slug' => 'own-sport',
    ]);

    $otherSport = Sport::factory()->create([
        'organization_id' => $organizationB->id,
        'slug' => 'other-sport',
    ]);

    expect(Gate::forUser($user)->allows('manage-tenant-record', $ownSport))->toBeTrue()
        ->and(Gate::forUser($user)->allows('manage-tenant-record', $otherSport))->toBeFalse();
});
