<?php

use App\Models\Organization;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\Demo\DemoCatalog;
use Database\Seeders\Demo\DemoSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds a realistic football and basketball demo setup', function (): void {
    $this->seed(DemoSeeder::class);

    $organization = Organization::query()
        ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
        ->first();

    expect($organization)->not->toBeNull();

    $footballTournament = Tournament::query()
        ->where('organization_id', $organization->id)
        ->where('name', DemoCatalog::tournaments()['football']['name'])
        ->first();

    $basketballTournament = Tournament::query()
        ->where('organization_id', $organization->id)
        ->where('name', DemoCatalog::tournaments()['basketball']['name'])
        ->first();

    expect($footballTournament)->not->toBeNull()
        ->and($basketballTournament)->not->toBeNull()
        ->and($footballTournament->entries()->count())->toBe(6)
        ->and($basketballTournament->entries()->count())->toBe(4)
        ->and($footballTournament->matches()->count())->toBeGreaterThan(0)
        ->and($basketballTournament->matches()->count())->toBeGreaterThan(0)
        ->and($footballTournament->matches()->whereHas('result')->count())->toBeGreaterThan(0)
        ->and($basketballTournament->matches()->whereHas('result')->count())->toBeGreaterThan(0);
});

it('seeds demo users that can authenticate locally', function (): void {
    $this->seed(DemoSeeder::class);

    $organization = Organization::query()
        ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
        ->firstOrFail();

    foreach (DemoCatalog::users() as $demoUser) {
        $user = User::query()
            ->where('email', $demoUser['email'])
            ->first();

        expect($user)->not->toBeNull()
            ->and(Hash::check(DemoCatalog::DEMO_PASSWORD, $user->password))->toBeTrue()
            ->and($user->current_organization_id)->toBe($organization->id)
            ->and($user->belongsToOrganizationId($organization->id))->toBeTrue();
    }
});
