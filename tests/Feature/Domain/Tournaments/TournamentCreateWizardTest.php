<?php

declare(strict_types=1);

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Tournaments\Create as TournamentCreate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $this->user = User::factory()->create();
    $this->user->organizations()->attach($this->organization->id);
    $this->user->update(['current_organization_id' => $this->organization->id]);

    $this->actingAs($this->user);
});

it('validates and progresses through wizard steps before creating tournament with participants', function (): void {
    $event = Event::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'wizard-sport-1',
    ]);

    $category = Category::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $sport->id,
    ]);

    $teamOne = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $sport->id,
        'category_id' => $category->id,
        'name' => 'Alpha Team',
    ]);

    $teamTwo = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $sport->id,
        'category_id' => $category->id,
        'name' => 'Beta Team',
    ]);

    $component = Livewire::test(TournamentCreate::class)
        ->call('goToNextStep')
        ->assertHasErrors(['name', 'event_id', 'sport_id'])
        ->assertSet('currentStep', 1)
        ->set('name', 'Wizard Cup')
        ->set('event_id', $event->id)
        ->set('sport_id', $sport->id)
        ->set('category_id', $category->id)
        ->call('goToNextStep')
        ->assertSet('currentStep', 2)
        ->set('participant_team_id', $teamOne->id)
        ->set('participant_seed', 1)
        ->call('addParticipantTeam')
        ->set('participant_team_id', $teamTwo->id)
        ->set('participant_seed', 2)
        ->call('addParticipantTeam')
        ->call('goToNextStep')
        ->assertSet('currentStep', 3)
        ->set('type', TournamentType::HalfCompetition->value)
        ->set('status', TournamentStatus::Draft->value)
        ->call('goToNextStep')
        ->assertSet('currentStep', 4)
        ->call('save')
        ->assertHasNoErrors();

    $tournament = Tournament::query()
        ->where('organization_id', $this->organization->id)
        ->where('name', 'Wizard Cup')
        ->firstOrFail();

    expect($tournament->sport_id)->toBe($sport->id)
        ->and($tournament->category_id)->toBe($category->id)
        ->and(TournamentEntry::query()->where('tournament_id', $tournament->id)->count())->toBe(2)
        ->and(TournamentEntry::query()->where('tournament_id', $tournament->id)->where('team_id', $teamOne->id)->value('seed'))->toBe(1)
        ->and(TournamentEntry::query()->where('tournament_id', $tournament->id)->where('team_id', $teamTwo->id)->value('seed'))->toBe(2);

    $component->assertRedirect(route('tournaments.index', absolute: false));
});

it('supports quick create for sport category event and team inside wizard', function (): void {
    Livewire::test(TournamentCreate::class)
        ->set('quick_sport_name', 'Inline Sport')
        ->call('createQuickSport')
        ->assertHasNoErrors()
        ->set('quick_category_name', 'Inline Category')
        ->set('quick_category_sport_id', Sport::query()->where('organization_id', $this->organization->id)->where('name', 'Inline Sport')->value('id'))
        ->call('createQuickCategory')
        ->assertHasNoErrors()
        ->set('quick_event_name', 'Inline Event')
        ->set('quick_event_status', 'draft')
        ->call('createQuickEvent')
        ->assertHasNoErrors()
        ->set('quick_team_name', 'Inline Team')
        ->set('quick_team_short_name', 'ILT')
        ->call('createQuickTeam')
        ->assertHasNoErrors();

    $sport = Sport::query()->where('organization_id', $this->organization->id)->where('name', 'Inline Sport')->first();
    $category = Category::query()->where('organization_id', $this->organization->id)->where('name', 'Inline Category')->first();
    $event = Event::query()->where('organization_id', $this->organization->id)->where('name', 'Inline Event')->first();
    $team = Team::query()->where('organization_id', $this->organization->id)->where('name', 'Inline Team')->first();

    expect($sport)->not->toBeNull()
        ->and($category)->not->toBeNull()
        ->and($event)->not->toBeNull()
        ->and($team)->not->toBeNull();
});

it('blocks csv import for free subscriptions', function (): void {
    $this->organization->forceFill([
        'subscription_plan' => BillingPlan::Free,
        'subscription_status' => SubscriptionStatus::Active,
    ])->save();

    $sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'wizard-sport-2',
    ]);

    $file = UploadedFile::fake()->createWithContent('participants.csv', "name,short_name,seed\nOne Team,ONE,1\n");

    Livewire::test(TournamentCreate::class)
        ->set('sport_id', $sport->id)
        ->set('participants_csv', $file)
        ->call('importParticipantsCsv')
        ->assertHasErrors(['participants_csv']);
});

it('imports csv participants for paid subscriptions and reports row errors', function (): void {
    $sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'wizard-sport-3',
    ]);

    $existingTeam = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $sport->id,
        'category_id' => null,
        'name' => 'Existing Team',
    ]);

    $file = UploadedFile::fake()->createWithContent('participants.csv', implode("\n", [
        'name,short_name,seed',
        'Existing Team,EXT,1',
        'New Team,NEW,2',
        ',MISS,3',
        'Bad Seed,BAD,abc',
        'Existing Team,EXT,4',
    ]));

    Livewire::test(TournamentCreate::class)
        ->set('sport_id', $sport->id)
        ->set('participant_entries', [
            (string) $existingTeam->id => [
                'team_id' => $existingTeam->id,
                'seed' => 1,
            ],
        ])
        ->set('participants_csv', $file)
        ->call('importParticipantsCsv')
        ->assertHasNoErrors()
        ->assertSet('import_status', 'Imported 1 participant(s).');

    $newTeam = Team::query()
        ->where('organization_id', $this->organization->id)
        ->where('sport_id', $sport->id)
        ->where('name', 'New Team')
        ->first();

    expect($newTeam)->not->toBeNull();
});
