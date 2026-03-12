<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Domain\Tournaments\Exceptions\TournamentMatchesAlreadyGeneratedException;
use App\Domain\Tournaments\Services\GenerateTournamentMatches;
use App\Models\Event;
use App\Models\Field;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\Venue;

it('generates and persists matches for a half competition tournament', function (): void {
    $organization = Organization::factory()->create();
    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'football-half',
    ]);
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $venue = Venue::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sport->id,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'type' => TournamentType::HalfCompetition,
        'final_type' => TournamentFinalType::None,
        'scheduled_start_at' => '2026-03-12 09:00:00',
        'match_duration_minutes' => 30,
        'break_duration_minutes' => 10,
    ]);

    $teams = Team::factory()->count(4)->create([
        'organization_id' => $organization->id,
        'category_id' => null,
    ]);

    foreach ($teams as $team) {
        TournamentEntry::factory()->create([
            'organization_id' => $organization->id,
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
            'player_id' => null,
        ]);
    }

    $service = app(GenerateTournamentMatches::class);

    $matches = $service->handle($tournament);

    expect($matches)->toHaveCount(6)
        ->and($tournament->matches()->count())->toBe(6)
        ->and($matches->first()->starts_at)->not->toBeNull();
});

it('generates and persists matches for a full competition tournament', function (): void {
    $organization = Organization::factory()->create();
    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'football-full',
    ]);
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'type' => TournamentType::FullCompetition,
        'final_type' => TournamentFinalType::None,
        'scheduled_start_at' => null,
        'match_duration_minutes' => null,
        'break_duration_minutes' => null,
    ]);

    $teams = Team::factory()->count(4)->create([
        'organization_id' => $organization->id,
        'category_id' => null,
    ]);

    foreach ($teams as $team) {
        TournamentEntry::factory()->create([
            'organization_id' => $organization->id,
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
            'player_id' => null,
        ]);
    }

    $service = app(GenerateTournamentMatches::class);

    $matches = $service->handle($tournament);

    expect($matches)->toHaveCount(12)
        ->and($tournament->matches()->count())->toBe(12)
        ->and($matches->first()->starts_at)->toBeNull();
});

it('refuses duplicate generation unless force is passed', function (): void {
    $organization = Organization::factory()->create();
    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'football-duplicate',
    ]);
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'type' => TournamentType::HalfCompetition,
        'final_type' => TournamentFinalType::None,
        'scheduled_start_at' => null,
        'match_duration_minutes' => null,
        'break_duration_minutes' => null,
    ]);

    $teams = Team::factory()->count(4)->create([
        'organization_id' => $organization->id,
        'category_id' => null,
    ]);

    foreach ($teams as $team) {
        TournamentEntry::factory()->create([
            'organization_id' => $organization->id,
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
            'player_id' => null,
        ]);
    }

    $service = app(GenerateTournamentMatches::class);

    $service->handle($tournament);

    expect(fn () => $service->handle($tournament))
        ->toThrow(TournamentMatchesAlreadyGeneratedException::class);

    $service->handle($tournament, force: true);

    expect($tournament->matches()->count())->toBe(6);
});
