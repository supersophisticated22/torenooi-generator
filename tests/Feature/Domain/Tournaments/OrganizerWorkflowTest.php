<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Categories\Create as CategoryCreate;
use App\Livewire\Events\Create as EventCreate;
use App\Livewire\Fields\Create as FieldCreate;
use App\Livewire\Matches\Score as MatchScore;
use App\Livewire\Players\Create as PlayerCreate;
use App\Livewire\Sports\Create as SportCreate;
use App\Livewire\Teams\Create as TeamCreate;
use App\Livewire\Teams\Players as TeamPlayers;
use App\Livewire\Tournaments\Create as TournamentCreate;
use App\Livewire\Tournaments\Entries as TournamentEntries;
use App\Livewire\Tournaments\Show as TournamentShow;
use App\Livewire\Venues\Create as VenueCreate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

it('supports the full organizer workflow end-to-end', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create([
        'slug' => 'organizer-workflow-org',
    ]);

    $user->organizations()->attach($organization->id);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user);

    Livewire::test(SportCreate::class)
        ->set('name', 'Football')
        ->set('win_points', 3)
        ->set('draw_points', 1)
        ->set('loss_points', 0)
        ->call('save')
        ->assertHasNoErrors();

    $sport = Sport::query()->where('organization_id', $organization->id)->where('name', 'Football')->firstOrFail();

    Livewire::test(CategoryCreate::class)
        ->set('name', 'Senior')
        ->set('sport_id', $sport->id)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(CategoryCreate::class)
        ->set('name', 'Junior')
        ->set('sport_id', $sport->id)
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::query()->where('organization_id', $organization->id)->where('name', 'Senior')->firstOrFail();

    Livewire::test(TeamCreate::class)
        ->set('name', 'Lions')
        ->set('short_name', 'LIO')
        ->set('sport_id', $sport->id)
        ->set('category_id', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(TeamCreate::class)
        ->set('name', 'Tigers')
        ->set('short_name', 'TIG')
        ->set('sport_id', $sport->id)
        ->set('category_id', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $homeTeam = Team::query()->where('organization_id', $organization->id)->where('name', 'Lions')->firstOrFail();
    $awayTeam = Team::query()->where('organization_id', $organization->id)->where('name', 'Tigers')->firstOrFail();

    Livewire::test(PlayerCreate::class)
        ->set('first_name', 'Alice')
        ->set('last_name', 'Striker')
        ->set('email', 'alice@workflow.test')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(PlayerCreate::class)
        ->set('first_name', 'Bob')
        ->set('last_name', 'Defender')
        ->set('email', 'bob@workflow.test')
        ->call('save')
        ->assertHasNoErrors();

    $alice = Player::query()->where('organization_id', $organization->id)->where('email', 'alice@workflow.test')->firstOrFail();
    $bob = Player::query()->where('organization_id', $organization->id)->where('email', 'bob@workflow.test')->firstOrFail();

    Livewire::test(TeamPlayers::class, ['team' => $homeTeam])
        ->set('player_id', $alice->id)
        ->set('jersey_number', 9)
        ->call('assignPlayer')
        ->assertHasNoErrors();

    Livewire::test(TeamPlayers::class, ['team' => $awayTeam])
        ->set('player_id', $bob->id)
        ->set('jersey_number', 4)
        ->call('assignPlayer')
        ->assertHasNoErrors();

    Livewire::test(VenueCreate::class)
        ->set('name', 'Main Arena')
        ->set('address', '123 Center Street')
        ->call('save')
        ->assertHasNoErrors();

    $venue = Venue::query()->where('organization_id', $organization->id)->where('name', 'Main Arena')->firstOrFail();

    Livewire::test(FieldCreate::class)
        ->set('name', 'Field A')
        ->set('code', 'A')
        ->set('venue_id', $venue->id)
        ->set('sport_id', $sport->id)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(EventCreate::class)
        ->set('name', 'Summer Cup')
        ->set('starts_at', '2026-07-01T09:00')
        ->set('ends_at', '2026-07-01T21:00')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('organization_id', $organization->id)->where('name', 'Summer Cup')->firstOrFail();

    Livewire::test(TournamentCreate::class)
        ->set('name', 'Main Tournament')
        ->set('event_id', $event->id)
        ->set('sport_id', $sport->id)
        ->set('category_id', $category->id)
        ->set('type', TournamentType::HalfCompetition->value)
        ->set('final_type', 'none')
        ->set('pool_count', 0)
        ->set('status', TournamentStatus::Draft->value)
        ->call('save')
        ->assertHasNoErrors();

    $tournament = Tournament::query()->where('organization_id', $organization->id)->where('name', 'Main Tournament')->firstOrFail();

    Livewire::test(TournamentEntries::class, ['tournament' => $tournament])
        ->set('team_id', $homeTeam->id)
        ->set('seed', 1)
        ->call('addTeam')
        ->assertHasNoErrors();

    Livewire::test(TournamentEntries::class, ['tournament' => $tournament])
        ->set('team_id', $awayTeam->id)
        ->set('seed', 2)
        ->call('addTeam')
        ->assertHasNoErrors();

    expect(TournamentEntry::query()->where('tournament_id', $tournament->id)->count())->toBe(2);

    Livewire::test(TournamentShow::class, ['tournament' => $tournament])
        ->set('tab', 'matches')
        ->call('generateMatches')
        ->assertHasNoErrors();

    $match = TournamentMatch::query()->where('tournament_id', $tournament->id)->firstOrFail();

    Livewire::test(MatchScore::class, ['match' => $match])
        ->set('home_score', 2)
        ->set('away_score', 0)
        ->call('saveScore')
        ->assertHasNoErrors()
        ->set('event_type', 'goal')
        ->set('team_id', $homeTeam->id)
        ->set('minute', 10)
        ->call('addEvent')
        ->assertHasNoErrors()
        ->call('completeMatch')
        ->assertHasNoErrors();

    $match->refresh();

    expect($match->status->value)->toBe('completed')
        ->and($match->result)->not->toBeNull();

    Livewire::test(TournamentShow::class, ['tournament' => $tournament])
        ->set('tab', 'standings')
        ->assertSee('Lions')
        ->assertSee('3')
        ->assertSee('Tigers');

    $this->get(route('scores.public', ['organization' => $organization->slug, 'tournament' => $tournament->id]))
        ->assertOk()
        ->assertSee('Public Score Screen')
        ->assertSee('Lions')
        ->assertSee('2 : 0');
});
