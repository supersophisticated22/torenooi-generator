<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Matches\Score as MatchScore;
use App\Livewire\Referees\Create as RefereeCreate;
use App\Livewire\Referees\Edit as RefereeEdit;
use App\Livewire\Tournaments\Show as TournamentShow;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Referee;
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
        'slug' => 'referee-sport',
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

test('referee can be created and updated with optional sport', function (): void {
    Livewire::test(RefereeCreate::class)
        ->set('first_name', 'Robin')
        ->set('last_name', 'Miller')
        ->set('email', 'robin@example.com')
        ->set('phone', '+31123456789')
        ->set('sport_id', $this->sport->id)
        ->call('save')
        ->assertHasNoErrors();

    $referee = Referee::query()
        ->where('organization_id', $this->organization->id)
        ->where('email', 'robin@example.com')
        ->firstOrFail();

    expect($referee->sport_id)->toBe($this->sport->id);

    Livewire::test(RefereeEdit::class, ['referee' => $referee])
        ->set('first_name', 'Rob')
        ->set('last_name', 'Miller')
        ->set('email', 'rob@example.com')
        ->set('phone', null)
        ->set('sport_id', null)
        ->call('save')
        ->assertHasNoErrors();

    $referee->refresh();

    expect($referee->first_name)->toBe('Rob')
        ->and($referee->email)->toBe('rob@example.com')
        ->and($referee->sport_id)->toBeNull();
});

test('tournament can assign and remove referees with sport validation', function (): void {
    $validReferee = Referee::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
    ]);

    $otherSport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'other-referee-sport',
    ]);

    $invalidReferee = Referee::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $otherSport->id,
    ]);

    Livewire::test(TournamentShow::class, ['tournament' => $this->tournament])
        ->set('tab', 'referees')
        ->set('tournament_referee_id', $validReferee->id)
        ->call('assignReferee')
        ->assertHasNoErrors()
        ->set('tournament_referee_id', $invalidReferee->id)
        ->call('assignReferee')
        ->assertHasErrors(['tournament_referee_id']);

    Livewire::test(TournamentShow::class, ['tournament' => $this->tournament])
        ->set('tab', 'referees')
        ->call('removeReferee', $validReferee->id)
        ->assertHasNoErrors();

    expect($this->tournament->referees()->whereKey($validReferee->id)->exists())->toBeFalse()
        ->and($this->tournament->referees()->whereKey($invalidReferee->id)->exists())->toBeFalse();
});

test('match supports multiple referees and blocks duplicate assignment', function (): void {
    $firstReferee = Referee::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => null,
    ]);

    $secondReferee = Referee::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
    ]);

    Livewire::test(MatchScore::class, ['match' => $this->match])
        ->set('referee_id', $firstReferee->id)
        ->call('assignReferee')
        ->assertHasNoErrors()
        ->set('referee_id', $firstReferee->id)
        ->call('assignReferee')
        ->assertHasErrors(['referee_id'])
        ->set('referee_id', $secondReferee->id)
        ->call('assignReferee')
        ->assertHasNoErrors()
        ->call('removeReferee', $firstReferee->id)
        ->assertHasNoErrors();

    expect($this->match->referees()->whereKey($firstReferee->id)->exists())->toBeFalse()
        ->and($this->match->referees()->whereKey($secondReferee->id)->exists())->toBeTrue();
});
