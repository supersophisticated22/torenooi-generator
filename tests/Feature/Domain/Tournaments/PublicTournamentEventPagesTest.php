<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\ScoreScreen\Event as PublicEventScreen;
use App\Livewire\ScoreScreen\Tournament as PublicTournamentScreen;
use App\Models\Event;
use App\Models\Field;
use App\Models\MatchResult;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\Venue;
use Livewire\Livewire;

it('shows public tournament page with summary teams schedule results and standings', function (): void {
    $organization = Organization::factory()->create();

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'public-tournament-page-sport',
    ]);

    $sport->sportRule()->create([
        'organization_id' => $organization->id,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Public Tournament Event',
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Public Tournament Detail',
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $venue = Venue::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $fieldA = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sport->id,
        'name' => 'Field One',
    ]);

    $fieldB = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sport->id,
        'name' => 'Field Two',
    ]);

    $alpha = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Tournament Alpha',
    ]);

    $beta = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Tournament Beta',
    ]);

    TournamentEntry::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $alpha->id,
        'player_id' => null,
    ]);

    TournamentEntry::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $beta->id,
        'player_id' => null,
    ]);

    $completedMatch = TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => null,
        'home_team_id' => $alpha->id,
        'away_team_id' => $beta->id,
        'field_id' => $fieldA->id,
        'referee_id' => null,
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHours(2),
        'status' => MatchStatus::Completed,
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => null,
        'home_team_id' => $beta->id,
        'away_team_id' => $alpha->id,
        'field_id' => $fieldB->id,
        'referee_id' => null,
        'starts_at' => now()->addHour(),
        'status' => MatchStatus::Scheduled,
    ]);

    MatchResult::query()->create([
        'organization_id' => $organization->id,
        'match_id' => $completedMatch->id,
        'home_score' => 2,
        'away_score' => 0,
        'winner_team_id' => $alpha->id,
        'notes' => null,
    ]);

    $this->get(route('scores.public.tournament', ['organization' => $organization->slug, 'tournament' => $tournament->id]))
        ->assertOk()
        ->assertSee('Public Tournament Detail')
        ->assertSee('Teams')
        ->assertSee('Schedule')
        ->assertSee('Results')
        ->assertSee('Standings')
        ->assertSee('Tournament Alpha')
        ->assertSee('2 : 0');
});

it('filters public tournament page by field', function (): void {
    $organization = Organization::factory()->create();

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'public-tournament-filter-sport',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
    ]);

    $venue = Venue::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $fieldA = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sport->id,
        'name' => 'Alpha Field',
    ]);

    $fieldB = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sport->id,
        'name' => 'Bravo Field',
    ]);

    $alpha = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Field Alpha Team',
    ]);

    $beta = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Field Beta Team',
    ]);

    $gamma = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Field Gamma Team',
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => null,
        'home_team_id' => $alpha->id,
        'away_team_id' => $beta->id,
        'field_id' => $fieldA->id,
        'referee_id' => null,
        'starts_at' => now()->addHour(),
        'status' => MatchStatus::Scheduled,
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => null,
        'home_team_id' => $gamma->id,
        'away_team_id' => $beta->id,
        'field_id' => $fieldB->id,
        'referee_id' => null,
        'starts_at' => now()->addHours(2),
        'status' => MatchStatus::Scheduled,
    ]);

    Livewire::test(PublicTournamentScreen::class, ['organization' => $organization, 'tournament' => $tournament])
        ->assertSee('Field Alpha Team')
        ->assertSee('Field Gamma Team')
        ->set('field_id', $fieldA->id)
        ->assertSee('Field Alpha Team')
        ->assertDontSee('Field Gamma Team');
});

it('does not expose tournament of another organization on public tournament page', function (): void {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $sportA = Sport::factory()->create([
        'organization_id' => $organizationA->id,
        'slug' => 'org-a-public-tournament-sport',
    ]);

    $sportB = Sport::factory()->create([
        'organization_id' => $organizationB->id,
        'slug' => 'org-b-public-tournament-sport',
    ]);

    $eventA = Event::factory()->create([
        'organization_id' => $organizationA->id,
    ]);

    $eventB = Event::factory()->create([
        'organization_id' => $organizationB->id,
    ]);

    Tournament::factory()->create([
        'organization_id' => $organizationA->id,
        'event_id' => $eventA->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
    ]);

    $foreignTournament = Tournament::factory()->create([
        'organization_id' => $organizationB->id,
        'event_id' => $eventB->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
    ]);

    $this->get(route('scores.public.tournament', ['organization' => $organizationA->slug, 'tournament' => $foreignTournament->id]))
        ->assertNotFound();
});

it('does not expose tournament when parent event is private on public tournament page', function (): void {
    $organization = Organization::factory()->create();

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'private-event-tournament-sport',
    ]);

    $privateEvent = Event::factory()->create([
        'organization_id' => $organization->id,
        'is_private' => true,
    ]);

    $privateTournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $privateEvent->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Private Event Tournament',
    ]);

    $this->get(route('scores.public.tournament', ['organization' => $organization->slug, 'tournament' => $privateTournament->id]))
        ->assertNotFound();
});

it('shows and filters public event page by sport tournament and field', function (): void {
    $organization = Organization::factory()->create();

    $sportA = Sport::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Event Sport A',
        'slug' => 'event-sport-a-public',
    ]);

    $sportB = Sport::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Event Sport B',
        'slug' => 'event-sport-b-public',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Public Event Detail',
    ]);

    $tournamentA = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Event Tournament A',
    ]);

    $tournamentB = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Event Tournament B',
    ]);

    $venue = Venue::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $fieldA = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sportA->id,
        'name' => 'Event Field A',
    ]);

    $fieldB = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venue->id,
        'sport_id' => $sportB->id,
        'name' => 'Event Field B',
    ]);

    $alpha = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Event Alpha Team',
    ]);

    $beta = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Event Beta Team',
    ]);

    $gamma = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Event Gamma Team',
    ]);

    $delta = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Event Delta Team',
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournamentA->id,
        'pool_id' => null,
        'home_team_id' => $alpha->id,
        'away_team_id' => $beta->id,
        'field_id' => $fieldA->id,
        'referee_id' => null,
        'starts_at' => now()->addHour(),
        'status' => MatchStatus::Scheduled,
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournamentB->id,
        'pool_id' => null,
        'home_team_id' => $gamma->id,
        'away_team_id' => $delta->id,
        'field_id' => $fieldB->id,
        'referee_id' => null,
        'starts_at' => now()->addHours(2),
        'status' => MatchStatus::Scheduled,
    ]);

    $this->get(route('scores.public.event', ['organization' => $organization->slug, 'eventSlug' => $event->slug]))
        ->assertOk()
        ->assertSee('Public Event Detail')
        ->assertSee('Tournament summary')
        ->assertSee('Teams')
        ->assertSee('Schedule')
        ->assertSee('Results')
        ->assertSee('Standings');

    Livewire::test(PublicEventScreen::class, ['organization' => $organization, 'eventSlug' => $event->slug])
        ->assertSee('Event Alpha Team')
        ->assertSee('Event Gamma Team')
        ->set('sport_id', $sportA->id)
        ->assertSee('Event Alpha Team')
        ->assertDontSee('Event Gamma Team')
        ->set('sport_id', null)
        ->set('tournament_id', $tournamentB->id)
        ->assertSee('Event Gamma Team')
        ->assertDontSee('Event Alpha Team')
        ->set('tournament_id', null)
        ->set('field_id', $fieldA->id)
        ->assertSee('Event Alpha Team')
        ->assertDontSee('Event Gamma Team');
});

it('does not expose event of another organization on public event page', function (): void {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $eventA = Event::factory()->create([
        'organization_id' => $organizationA->id,
    ]);

    $eventB = Event::factory()->create([
        'organization_id' => $organizationB->id,
    ]);

    expect($eventA->id)->not->toBe($eventB->id);

    $this->get(route('scores.public.event', ['organization' => $organizationA->slug, 'eventSlug' => $eventB->slug]))
        ->assertNotFound();
});

it('does not expose private event on public event page', function (): void {
    $organization = Organization::factory()->create();

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'private-event-page',
        'is_private' => true,
    ]);

    $this->get(route('scores.public.event', ['organization' => $organization->slug, 'eventSlug' => $event->slug]))
        ->assertNotFound();
});
