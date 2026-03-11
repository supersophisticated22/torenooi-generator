<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\EventStatus;
use App\Domain\Tournaments\Enums\MatchEventType;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Models\Event;
use App\Models\Field;
use App\Models\MatchEvent;
use App\Models\MatchResult;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Pool;
use App\Models\PoolEntry;
use App\Models\Sport;
use App\Models\SportRule;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\Venue;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('creates all mvp domain tables', function (): void {
    $tables = [
        'organizations',
        'sports',
        'sport_rules',
        'categories',
        'teams',
        'players',
        'team_player',
        'venues',
        'fields',
        'referees',
        'events',
        'tournaments',
        'tournament_entries',
        'pools',
        'pool_entries',
        'matches',
        'match_results',
        'match_events',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('enforces xor participant rule on tournament entries', function (): void {
    $organization = Organization::factory()->create();
    $event = Event::factory()->for($organization)->create();
    $sport = Sport::factory()->for($organization)->create();
    $tournament = Tournament::factory()
        ->for($organization)
        ->for($event)
        ->for($sport)
        ->create();

    expect(fn () => TournamentEntry::query()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => null,
        'player_id' => null,
    ]))->toThrow(QueryException::class);

    $team = Team::factory()->for($organization)->create();
    $player = Player::factory()->for($organization)->create();

    expect(fn () => TournamentEntry::query()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $team->id,
        'player_id' => $player->id,
    ]))->toThrow(QueryException::class);

    $valid = TournamentEntry::query()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $team->id,
        'player_id' => null,
    ]);

    expect($valid->exists)->toBeTrue();
});

it('stores sport rule points and resolves sport relation', function (): void {
    $organization = Organization::factory()->create();
    $sport = Sport::factory()->for($organization)->create();

    $rule = SportRule::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'win_points' => 4,
        'draw_points' => 2,
        'loss_points' => 1,
    ]);

    expect($rule->sport->is($sport))->toBeTrue()
        ->and($rule->win_points)->toBe(4)
        ->and($rule->draw_points)->toBe(2)
        ->and($rule->loss_points)->toBe(1);
});

it('resolves key tournament, pool, and match relations', function (): void {
    $organization = Organization::factory()->create();
    $event = Event::factory()->for($organization)->create();
    $sport = Sport::factory()->for($organization)->create();
    $venue = Venue::factory()->for($organization)->create();
    $field = Field::factory()->for($organization)->for($venue)->for($sport)->create();

    $homeTeam = Team::factory()->for($organization)->create();
    $awayTeam = Team::factory()->for($organization)->create();

    $tournament = Tournament::factory()
        ->for($organization)
        ->for($event)
        ->for($sport)
        ->create();

    $entry = TournamentEntry::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $homeTeam->id,
        'player_id' => null,
    ]);

    $pool = Pool::factory()->for($organization)->for($tournament)->create();

    PoolEntry::query()->create([
        'organization_id' => $organization->id,
        'pool_id' => $pool->id,
        'tournament_entry_id' => $entry->id,
        'seed' => 1,
    ]);

    $match = TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => $pool->id,
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'field_id' => $field->id,
        'status' => MatchStatus::Scheduled,
    ]);

    MatchResult::query()->create([
        'organization_id' => $organization->id,
        'match_id' => $match->id,
        'home_score' => 2,
        'away_score' => 1,
        'winner_team_id' => $homeTeam->id,
    ]);

    $freshTournament = $tournament->fresh('entries');
    $freshPool = $pool->fresh('entries');
    $freshMatch = $match->fresh('result');

    expect($freshTournament->entries)->toHaveCount(1)
        ->and($freshPool->entries)->toHaveCount(1)
        ->and($freshMatch->result)->not->toBeNull();
});

it('casts enums on event, tournament, match, and match event models', function (): void {
    $organization = Organization::factory()->create();
    $event = Event::factory()->for($organization)->create(['status' => EventStatus::Active]);
    $sport = Sport::factory()->for($organization)->create();

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'type' => TournamentType::Knockout,
        'final_type' => TournamentFinalType::FinalAndThirdPlace,
        'status' => TournamentStatus::Scheduled,
    ]);

    $homeTeam = Team::factory()->for($organization)->create();
    $awayTeam = Team::factory()->for($organization)->create();

    $match = TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'status' => MatchStatus::InProgress,
    ]);

    $matchEvent = MatchEvent::query()->create([
        'organization_id' => $organization->id,
        'match_id' => $match->id,
        'team_id' => $homeTeam->id,
        'player_id' => null,
        'event_type' => MatchEventType::Goal,
        'minute' => 14,
    ]);

    expect($event->fresh()->status)->toBeInstanceOf(EventStatus::class)
        ->and($tournament->fresh()->type)->toBeInstanceOf(TournamentType::class)
        ->and($tournament->fresh()->final_type)->toBeInstanceOf(TournamentFinalType::class)
        ->and($tournament->fresh()->status)->toBeInstanceOf(TournamentStatus::class)
        ->and($match->fresh()->status)->toBeInstanceOf(MatchStatus::class)
        ->and($matchEvent->fresh()->event_type)->toBeInstanceOf(MatchEventType::class);
});
