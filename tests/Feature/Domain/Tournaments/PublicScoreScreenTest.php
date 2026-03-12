<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\ScoreScreen\Index as PublicScoreScreen;
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

it('allows public access to score screen and shows standings when available', function (): void {
    $organization = Organization::factory()->create();

    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'public-screen-sport',
    ]);

    $sport->sportRule()->create([
        'organization_id' => $organization->id,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Public Event',
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Public Tournament',
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $homeTeam = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Screen Home',
    ]);

    $awayTeam = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Screen Away',
    ]);

    TournamentEntry::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $homeTeam->id,
        'player_id' => null,
    ]);

    TournamentEntry::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'team_id' => $awayTeam->id,
        'player_id' => null,
    ]);

    $match = TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => null,
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'field_id' => null,
        'referee_id' => null,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(20),
        'status' => MatchStatus::Completed,
    ]);

    MatchResult::query()->create([
        'organization_id' => $organization->id,
        'match_id' => $match->id,
        'home_score' => 2,
        'away_score' => 1,
        'winner_team_id' => $homeTeam->id,
        'notes' => null,
    ]);

    $this->get(route('scores.public', ['organization' => $organization->slug]))
        ->assertOk()
        ->assertSee('Public Score Screen')
        ->assertSee('Latest results')
        ->assertSee('Standings')
        ->assertSee('Screen Home')
        ->assertSee('2 : 1');
});

it('filters public score screen by event tournament venue field and sport', function (): void {
    $organization = Organization::factory()->create();

    $sportA = Sport::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Sport A',
        'slug' => 'sport-a-public',
    ]);

    $sportB = Sport::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Sport B',
        'slug' => 'sport-b-public',
    ]);

    $eventA = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Event Alpha',
    ]);

    $eventB = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Event Bravo',
    ]);

    $venueA = Venue::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Venue Alpha',
    ]);

    $venueB = Venue::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Venue Bravo',
    ]);

    $fieldA = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venueA->id,
        'sport_id' => $sportA->id,
        'name' => 'Field Alpha',
    ]);

    $fieldB = Field::factory()->create([
        'organization_id' => $organization->id,
        'venue_id' => $venueB->id,
        'sport_id' => $sportB->id,
        'name' => 'Field Bravo',
    ]);

    $tournamentA = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $eventA->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Tournament Alpha',
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $tournamentB = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $eventB->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Tournament Bravo',
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $alphaOne = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Alpha One',
    ]);

    $alphaTwo = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Alpha Two',
    ]);

    $bravoOne = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Bravo One',
    ]);

    $bravoTwo = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Bravo Two',
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournamentA->id,
        'pool_id' => null,
        'home_team_id' => $alphaOne->id,
        'away_team_id' => $alphaTwo->id,
        'field_id' => $fieldA->id,
        'referee_id' => null,
        'starts_at' => now()->addHour(),
        'status' => MatchStatus::Scheduled,
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournamentB->id,
        'pool_id' => null,
        'home_team_id' => $bravoOne->id,
        'away_team_id' => $bravoTwo->id,
        'field_id' => $fieldB->id,
        'referee_id' => null,
        'starts_at' => now()->addHours(2),
        'status' => MatchStatus::Scheduled,
    ]);

    Livewire::test(PublicScoreScreen::class, ['organization' => $organization])
        ->assertSee('Alpha One')
        ->assertSee('Bravo One')
        ->set('sport_id', $sportA->id)
        ->assertSee('Alpha One')
        ->assertDontSee('Bravo One')
        ->set('sport_id', null)
        ->set('event_id', $eventB->id)
        ->assertSee('Bravo One')
        ->assertDontSee('Alpha One')
        ->set('event_id', null)
        ->set('venue_id', $venueA->id)
        ->assertSee('Alpha One')
        ->assertDontSee('Bravo One')
        ->set('venue_id', null)
        ->set('field_id', $fieldB->id)
        ->assertSee('Bravo One')
        ->assertDontSee('Alpha One')
        ->set('field_id', null)
        ->set('tournament_id', $tournamentA->id)
        ->assertSee('Alpha One')
        ->assertDontSee('Bravo One');
});

it('does not expose other organization data on public score screen', function (): void {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $sportA = Sport::factory()->create([
        'organization_id' => $organizationA->id,
        'slug' => 'org-a-sport',
    ]);

    $sportB = Sport::factory()->create([
        'organization_id' => $organizationB->id,
        'slug' => 'org-b-sport',
    ]);

    $eventA = Event::factory()->create([
        'organization_id' => $organizationA->id,
    ]);

    $eventB = Event::factory()->create([
        'organization_id' => $organizationB->id,
    ]);

    $tournamentA = Tournament::factory()->create([
        'organization_id' => $organizationA->id,
        'event_id' => $eventA->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Org A Tournament',
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $tournamentB = Tournament::factory()->create([
        'organization_id' => $organizationB->id,
        'event_id' => $eventB->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Org B Tournament',
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $aHome = Team::factory()->create([
        'organization_id' => $organizationA->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Org A Team',
    ]);
    $aAway = Team::factory()->create([
        'organization_id' => $organizationA->id,
        'sport_id' => $sportA->id,
        'category_id' => null,
        'name' => 'Org A Team 2',
    ]);

    $bHome = Team::factory()->create([
        'organization_id' => $organizationB->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Org B Team',
    ]);
    $bAway = Team::factory()->create([
        'organization_id' => $organizationB->id,
        'sport_id' => $sportB->id,
        'category_id' => null,
        'name' => 'Org B Team 2',
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organizationA->id,
        'tournament_id' => $tournamentA->id,
        'pool_id' => null,
        'home_team_id' => $aHome->id,
        'away_team_id' => $aAway->id,
        'field_id' => null,
        'referee_id' => null,
        'starts_at' => now()->addHour(),
        'status' => MatchStatus::Scheduled,
    ]);

    TournamentMatch::factory()->create([
        'organization_id' => $organizationB->id,
        'tournament_id' => $tournamentB->id,
        'pool_id' => null,
        'home_team_id' => $bHome->id,
        'away_team_id' => $bAway->id,
        'field_id' => null,
        'referee_id' => null,
        'starts_at' => now()->addHours(2),
        'status' => MatchStatus::Scheduled,
    ]);

    $this->get(route('scores.public', ['organization' => $organizationA->slug]))
        ->assertOk()
        ->assertSee('Org A Team')
        ->assertDontSee('Org B Team');

    Livewire::withQueryParams(['sport' => $sportB->id])
        ->test(PublicScoreScreen::class, ['organization' => $organizationA])
        ->assertDontSee('Org B Team')
        ->assertDontSee('Org A Team');
});
