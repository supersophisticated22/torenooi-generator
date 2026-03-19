<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Tournaments\Enums\EventStatus;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Events\Create as EventCreate;
use App\Livewire\Matches\Score as MatchScore;
use App\Livewire\Sports\Create as SportCreate;
use App\Models\Event;
use App\Models\MatchResult;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Livewire\Livewire;

function createUserWithRole(Organization $organization, OrganizationRole $role, bool $isPlatformAdmin = false): User
{
    $organization->update([
        'selected_plan' => BillingPlan::Starter,
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $user = User::factory()->create([
        'is_platform_admin' => $isPlatformAdmin,
    ]);

    $user->organizations()->attach($organization->id, ['role' => $role->value]);
    $user->update(['current_organization_id' => $organization->id]);

    return $user->fresh();
}

function createMatchContext(Organization $organization): TournamentMatch
{
    $sport = Sport::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'access-control-sport',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'status' => EventStatus::Published,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $organization->id,
        'event_id' => $event->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Scheduled,
    ]);

    $homeTeam = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
    ]);

    $awayTeam = Team::factory()->create([
        'organization_id' => $organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
    ]);

    return TournamentMatch::factory()->create([
        'organization_id' => $organization->id,
        'tournament_id' => $tournament->id,
        'pool_id' => null,
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'field_id' => null,
        'referee_id' => null,
        'status' => MatchStatus::Scheduled,
    ]);
}

test('organization admin can manage CRUD and match scores', function (): void {
    $organization = Organization::factory()->create();
    $user = createUserWithRole($organization, OrganizationRole::OrganizationAdmin);
    $match = createMatchContext($organization);

    $this->actingAs($user);

    Livewire::test(SportCreate::class)
        ->set('name', 'Football')
        ->set('win_points', 3)
        ->set('draw_points', 1)
        ->set('loss_points', 0)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(MatchScore::class, ['match' => $match])
        ->set('home_score', 2)
        ->set('away_score', 1)
        ->call('saveScore')
        ->assertHasNoErrors();

    expect(MatchResult::query()->where('match_id', $match->id)->exists())->toBeTrue();
});

test('event manager can manage event flow and scoring but not core CRUD', function (): void {
    $organization = Organization::factory()->create();
    $user = createUserWithRole($organization, OrganizationRole::EventManager);
    $match = createMatchContext($organization);

    $this->actingAs($user);

    $this->get(route('sports.create'))->assertForbidden();

    Livewire::test(EventCreate::class)
        ->set('name', 'Summer Cup')
        ->set('starts_at', '2026-07-10T10:00')
        ->set('ends_at', '2026-07-10T18:00')
        ->set('status', EventStatus::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(MatchScore::class, ['match' => $match])
        ->set('home_score', 1)
        ->set('away_score', 1)
        ->call('saveScore')
        ->assertHasNoErrors();
});

test('scorekeeper can enter scores but cannot access CRUD create pages', function (): void {
    $organization = Organization::factory()->create();
    $user = createUserWithRole($organization, OrganizationRole::Scorekeeper);
    $match = createMatchContext($organization);

    $this->actingAs($user);

    $this->get(route('events.create'))->assertForbidden();

    Livewire::test(MatchScore::class, ['match' => $match])
        ->set('home_score', 0)
        ->set('away_score', 3)
        ->call('saveScore')
        ->assertHasNoErrors();

    expect(MatchResult::query()->where('match_id', $match->id)->value('away_score'))->toBe(3);
});

test('viewer and referee cannot enter match scores', function (): void {
    $organization = Organization::factory()->create();
    $viewer = createUserWithRole($organization, OrganizationRole::Viewer);
    $referee = createUserWithRole($organization, OrganizationRole::Referee);
    $match = createMatchContext($organization);

    $this->actingAs($viewer);

    Livewire::test(MatchScore::class, ['match' => $match])
        ->set('home_score', 2)
        ->set('away_score', 2)
        ->call('saveScore')
        ->assertForbidden();

    $this->actingAs($referee);

    Livewire::test(MatchScore::class, ['match' => $match])
        ->set('home_score', 1)
        ->set('away_score', 0)
        ->call('saveScore')
        ->assertForbidden();

    expect(MatchResult::query()->where('match_id', $match->id)->exists())->toBeFalse();
});

test('platform admin is redirected to admin area on tenant routes', function (): void {
    $organization = Organization::factory()->create();
    $user = createUserWithRole($organization, OrganizationRole::Viewer, isPlatformAdmin: true);

    $this->actingAs($user);

    $this->get(route('sports.create'))
        ->assertRedirect(route('admin.organizations.index'));
});

test('public score page remains publicly accessible', function (): void {
    $organization = Organization::factory()->create([
        'slug' => 'public-access-control',
    ]);

    $this->get(route('scores.public', ['organization' => $organization->slug]))
        ->assertOk();
});
