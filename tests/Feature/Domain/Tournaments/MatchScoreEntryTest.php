<?php

declare(strict_types=1);

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Enums\MatchEventType;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Matches\Score;
use App\Models\Event;
use App\Models\MatchEvent;
use App\Models\MatchResult;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();

    $this->user->organizations()->attach($this->organization->id);
    $this->user->update(['current_organization_id' => $this->organization->id]);

    $this->sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'match-score-sport',
    ]);

    $this->sport->sportRule()->create([
        'organization_id' => $this->organization->id,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $this->event = Event::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->tournament = Tournament::factory()->create([
        'organization_id' => $this->organization->id,
        'event_id' => $this->event->id,
        'sport_id' => $this->sport->id,
        'category_id' => null,
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $this->homeTeam = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => null,
    ]);

    $this->awayTeam = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => null,
    ]);

    $this->homePlayer = Player::factory()->create([
        'organization_id' => $this->organization->id,
        'team_id' => $this->homeTeam->id,
    ]);

    $this->awayPlayer = Player::factory()->create([
        'organization_id' => $this->organization->id,
        'team_id' => $this->awayTeam->id,
    ]);

    $this->match = TournamentMatch::factory()->create([
        'organization_id' => $this->organization->id,
        'tournament_id' => $this->tournament->id,
        'pool_id' => null,
        'home_team_id' => $this->homeTeam->id,
        'away_team_id' => $this->awayTeam->id,
        'field_id' => null,
        'referee_id' => null,
        'status' => MatchStatus::Scheduled,
    ]);

    $this->actingAs($this->user);
});

test('score entry saves match result and events', function (): void {
    Livewire::test(Score::class, ['match' => $this->match])
        ->set('home_score', 3)
        ->set('away_score', 1)
        ->call('saveScore')
        ->assertHasNoErrors()
        ->set('event_type', MatchEventType::Goal->value)
        ->set('team_id', $this->homeTeam->id)
        ->set('player_id', $this->homePlayer->id)
        ->set('minute', 12)
        ->set('sequence', 1)
        ->set('notes', 'Opening goal')
        ->call('addEvent')
        ->assertHasNoErrors();

    $result = MatchResult::query()->where('match_id', $this->match->id)->firstOrFail();
    $event = MatchEvent::query()->where('match_id', $this->match->id)->firstOrFail();

    expect($result->home_score)->toBe(3)
        ->and($result->away_score)->toBe(1)
        ->and($result->winner_team_id)->toBe($this->homeTeam->id)
        ->and($event->event_type)->toBe(MatchEventType::Goal)
        ->and($event->team_id)->toBe($this->homeTeam->id)
        ->and($event->player_id)->toBe($this->homePlayer->id)
        ->and($event->minute)->toBe(12)
        ->and($event->sequence)->toBe(1);
});

test('score entry allows note events and removing match events', function (): void {
    $component = Livewire::test(Score::class, ['match' => $this->match])
        ->set('event_type', MatchEventType::Note->value)
        ->set('team_id', null)
        ->set('player_id', null)
        ->set('minute', null)
        ->set('sequence', 4)
        ->set('notes', 'Weather delay')
        ->call('addEvent')
        ->assertHasNoErrors();

    $event = MatchEvent::query()->where('match_id', $this->match->id)->firstOrFail();

    expect($event->event_type)->toBe(MatchEventType::Note)
        ->and($event->team_id)->toBeNull()
        ->and($event->player_id)->toBeNull()
        ->and($event->sequence)->toBe(4);

    $component
        ->call('removeEvent', $event->id)
        ->assertHasNoErrors();

    expect(MatchEvent::query()->whereKey($event->id)->exists())->toBeFalse();
});

test('score entry rejects players outside the match teams', function (): void {
    $outsideTeam = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => null,
    ]);

    $outsidePlayer = Player::factory()->create([
        'organization_id' => $this->organization->id,
        'team_id' => $outsideTeam->id,
    ]);

    Livewire::test(Score::class, ['match' => $this->match])
        ->set('event_type', MatchEventType::YellowCard->value)
        ->set('team_id', $this->homeTeam->id)
        ->set('player_id', $outsidePlayer->id)
        ->call('addEvent')
        ->assertHasErrors(['player_id']);
});

test('match can be marked as completed after score is entered', function (): void {
    Livewire::test(Score::class, ['match' => $this->match])
        ->set('home_score', 2)
        ->set('away_score', 2)
        ->call('saveScore')
        ->assertHasNoErrors()
        ->call('completeMatch')
        ->assertHasNoErrors();

    $this->match->refresh();

    expect($this->match->status)->toBe(MatchStatus::Completed)
        ->and($this->match->ends_at)->not->toBeNull();
});

test('completing a match triggers standings recalculation service', function (): void {
    MatchResult::query()->create([
        'organization_id' => $this->organization->id,
        'match_id' => $this->match->id,
        'home_score' => 1,
        'away_score' => 0,
        'winner_team_id' => $this->homeTeam->id,
        'notes' => null,
    ]);

    $standingsRecalculationService = Mockery::mock(StandingsRecalculationService::class);
    $standingsRecalculationService
        ->shouldReceive('recalculateForTournament')
        ->once()
        ->with(Mockery::on(fn (Tournament $tournament): bool => $tournament->is($this->tournament)))
        ->andReturn([]);

    app()->instance(StandingsRecalculationService::class, $standingsRecalculationService);

    Livewire::test(Score::class, ['match' => $this->match])
        ->call('completeMatch')
        ->assertHasNoErrors();

    $this->match->refresh();

    expect($this->match->status)->toBe(MatchStatus::Completed);
});
