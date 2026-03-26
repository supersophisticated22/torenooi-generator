<?php

declare(strict_types=1);

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Tournaments\Enums\EventStatus;
use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Livewire\Events\Create as EventCreate;
use App\Livewire\Events\Edit as EventEdit;
use App\Livewire\Tournaments\Create as TournamentCreate;
use App\Livewire\Tournaments\Edit as TournamentEdit;
use App\Livewire\Tournaments\Entries as TournamentEntries;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();

    $this->user->organizations()->attach($this->organization->id);
    $this->user->update(['current_organization_id' => $this->organization->id]);

    $this->sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'event-tournament-sport',
    ]);

    $this->category = Category::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
    ]);

    $this->actingAs($this->user);
});

test('event can be created and updated', function (): void {
    Livewire::test(EventCreate::class)
        ->set('name', 'Summer Cup Weekend')
        ->set('starts_at', '2026-06-01T09:00')
        ->set('ends_at', '2026-06-01T18:00')
        ->set('status', EventStatus::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($event->name)->toBe('Summer Cup Weekend')
        ->and($event->status)->toBe(EventStatus::Published);

    Livewire::test(EventEdit::class, ['event' => $event])
        ->set('name', 'Summer Cup Finals')
        ->set('starts_at', '2026-06-02T10:00')
        ->set('ends_at', '2026-06-02T20:00')
        ->set('status', EventStatus::Active->value)
        ->call('save')
        ->assertHasNoErrors();

    $event->refresh();

    expect($event->name)->toBe('Summer Cup Finals')
        ->and($event->status)->toBe(EventStatus::Active);
});

test('private event creation requires paid subscription', function (): void {
    $this->organization->update([
        'subscription_plan' => BillingPlan::Free,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    Livewire::test(EventCreate::class)
        ->set('name', 'Private Event Attempt')
        ->set('status', EventStatus::Draft->value)
        ->set('is_private', true)
        ->call('save')
        ->assertHasErrors(['is_private']);

    expect(Event::query()
        ->where('organization_id', $this->organization->id)
        ->where('name', 'Private Event Attempt')
        ->exists())->toBeFalse();
});

test('private event update requires paid subscription', function (): void {
    $this->organization->update([
        'subscription_plan' => BillingPlan::Free,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    $event = Event::factory()->create([
        'organization_id' => $this->organization->id,
        'is_private' => false,
    ]);

    Livewire::test(EventEdit::class, ['event' => $event])
        ->set('is_private', true)
        ->call('save')
        ->assertHasErrors(['is_private']);

    $event->refresh();

    expect($event->is_private)->toBeFalse();
});

test('paid subscriptions can create private events', function (): void {
    $this->organization->update([
        'subscription_plan' => BillingPlan::Starter,
        'subscription_status' => SubscriptionStatus::Active,
    ]);

    Livewire::test(EventCreate::class)
        ->set('name', 'Paid Private Event')
        ->set('status', EventStatus::Published->value)
        ->set('is_private', true)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()
        ->where('organization_id', $this->organization->id)
        ->where('name', 'Paid Private Event')
        ->firstOrFail();

    expect($event->is_private)->toBeTrue();
});

test('tournament can be created and updated', function (): void {
    $event = Event::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => EventStatus::Published,
    ]);

    Livewire::test(TournamentCreate::class)
        ->set('name', 'Main Draw')
        ->set('event_id', $event->id)
        ->set('sport_id', $this->sport->id)
        ->set('category_id', $this->category->id)
        ->set('type', TournamentType::HalfCompetition->value)
        ->set('final_type', TournamentFinalType::FinalOnly->value)
        ->set('pool_count', 2)
        ->set('match_duration_minutes', 25)
        ->set('break_duration_minutes', 5)
        ->set('final_break_minutes', 10)
        ->set('scheduled_start_at', '2026-06-03T09:30')
        ->set('status', TournamentStatus::Scheduled->value)
        ->set('card_popup_enabled', true)
        ->set('card_popup_types', ['yellow_card', 'red_card'])
        ->set('card_popup_condition', 'threshold')
        ->set('card_popup_threshold', 2)
        ->call('save')
        ->assertHasNoErrors();

    $tournament = Tournament::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($tournament->name)->toBe('Main Draw')
        ->and($tournament->event_id)->toBe($event->id)
        ->and($tournament->sport_id)->toBe($this->sport->id)
        ->and($tournament->category_id)->toBe($this->category->id)
        ->and($tournament->type)->toBe(TournamentType::HalfCompetition)
        ->and($tournament->card_popup_settings)->toMatchArray([
            'enabled' => true,
            'card_types' => ['yellow_card', 'red_card'],
            'display' => [
                'condition' => 'threshold',
                'threshold' => 2,
            ],
        ])
        ->and($tournament->status)->toBe(TournamentStatus::Scheduled);

    Livewire::test(TournamentEdit::class, ['tournament' => $tournament])
        ->set('name', 'Main Draw Updated')
        ->set('event_id', $event->id)
        ->set('sport_id', $this->sport->id)
        ->set('category_id', null)
        ->set('type', TournamentType::FullCompetition->value)
        ->set('final_type', TournamentFinalType::None->value)
        ->set('pool_count', 0)
        ->set('match_duration_minutes', 30)
        ->set('break_duration_minutes', 8)
        ->set('final_break_minutes', 15)
        ->set('scheduled_start_at', '2026-06-03T10:30')
        ->set('status', TournamentStatus::Draft->value)
        ->set('card_popup_enabled', false)
        ->set('card_popup_types', ['green_card'])
        ->set('card_popup_condition', 'any_card')
        ->set('card_popup_threshold', null)
        ->call('save')
        ->assertHasNoErrors();

    $tournament->refresh();

    expect($tournament->name)->toBe('Main Draw Updated')
        ->and($tournament->category_id)->toBeNull()
        ->and($tournament->type)->toBe(TournamentType::FullCompetition)
        ->and($tournament->final_type)->toBe(TournamentFinalType::None)
        ->and($tournament->pool_count)->toBe(0)
        ->and($tournament->card_popup_settings)->toMatchArray([
            'enabled' => false,
            'card_types' => ['green_card'],
            'display' => [
                'condition' => 'any_card',
                'threshold' => null,
            ],
        ])
        ->and($tournament->status)->toBe(TournamentStatus::Draft);
});

test('tournament category must match tournament sport when category has sport', function (): void {
    $event = Event::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => EventStatus::Published,
    ]);

    $otherSport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'event-tournament-other-sport',
    ]);

    $otherCategory = Category::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $otherSport->id,
    ]);

    Livewire::test(TournamentCreate::class)
        ->set('name', 'Invalid Tournament')
        ->set('event_id', $event->id)
        ->set('sport_id', $this->sport->id)
        ->set('category_id', $otherCategory->id)
        ->set('type', TournamentType::HalfCompetition->value)
        ->set('final_type', TournamentFinalType::FinalOnly->value)
        ->set('pool_count', 2)
        ->set('status', TournamentStatus::Scheduled->value)
        ->call('save')
        ->assertHasErrors(['category_id']);
});

test('tournament team entry can be attached and updated', function (): void {
    $event = Event::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => EventStatus::Published,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $this->organization->id,
        'event_id' => $event->id,
        'sport_id' => $this->sport->id,
        'category_id' => $this->category->id,
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Draft,
    ]);

    $team = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(TournamentEntries::class, ['tournament' => $tournament])
        ->set('team_id', $team->id)
        ->set('seed', 3)
        ->call('addTeam')
        ->assertHasNoErrors();

    $entry = TournamentEntry::query()
        ->where('organization_id', $this->organization->id)
        ->where('tournament_id', $tournament->id)
        ->where('team_id', $team->id)
        ->firstOrFail();

    expect($entry->seed)->toBe(3);

    Livewire::test(TournamentEntries::class, ['tournament' => $tournament])
        ->set('seeds.'.$entry->id, 4)
        ->call('updateSeed', $entry->id)
        ->assertHasNoErrors();

    $entry->refresh();

    expect($entry->seed)->toBe(4);
});

test('tournament team entry allows empty seed and stores null', function (): void {
    $event = Event::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => EventStatus::Published,
    ]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $this->organization->id,
        'event_id' => $event->id,
        'sport_id' => $this->sport->id,
        'category_id' => $this->category->id,
        'type' => TournamentType::HalfCompetition,
        'status' => TournamentStatus::Draft,
    ]);

    $team = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(TournamentEntries::class, ['tournament' => $tournament])
        ->set('team_id', $team->id)
        ->set('seed', '')
        ->call('addTeam')
        ->assertHasNoErrors();

    $entry = TournamentEntry::query()
        ->where('organization_id', $this->organization->id)
        ->where('tournament_id', $tournament->id)
        ->where('team_id', $team->id)
        ->firstOrFail();

    expect($entry->seed)->toBeNull();
});
