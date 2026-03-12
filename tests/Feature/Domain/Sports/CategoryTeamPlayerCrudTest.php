<?php

declare(strict_types=1);

use App\Livewire\Categories\Create as CategoryCreate;
use App\Livewire\Categories\Edit as CategoryEdit;
use App\Livewire\Players\Create as PlayerCreate;
use App\Livewire\Players\Edit as PlayerEdit;
use App\Livewire\Teams\Create as TeamCreate;
use App\Livewire\Teams\Edit as TeamEdit;
use App\Livewire\Teams\Players as TeamPlayers;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();

    $this->user->organizations()->attach($this->organization->id);
    $this->user->update(['current_organization_id' => $this->organization->id]);

    $this->sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'crud-sport',
    ]);

    $this->actingAs($this->user);
});

test('category can be created and updated', function () {
    Livewire::test(CategoryCreate::class)
        ->set('name', 'Seniors')
        ->set('sport_id', $this->sport->id)
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($category->name)->toBe('Seniors')
        ->and($category->sport_id)->toBe($this->sport->id);

    Livewire::test(CategoryEdit::class, ['category' => $category])
        ->set('name', 'Juniors')
        ->set('sport_id', null)
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();

    expect($category->name)->toBe('Juniors')
        ->and($category->sport_id)->toBeNull();
});

test('team can be created and updated', function () {
    $category = Category::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
    ]);

    Livewire::test(TeamCreate::class)
        ->set('name', 'Red Wolves')
        ->set('short_name', 'RW')
        ->set('sport_id', $this->sport->id)
        ->set('category_id', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $team = Team::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($team->name)->toBe('Red Wolves')
        ->and($team->sport_id)->toBe($this->sport->id)
        ->and($team->category_id)->toBe($category->id);

    Livewire::test(TeamEdit::class, ['team' => $team])
        ->set('name', 'Blue Wolves')
        ->set('short_name', 'BW')
        ->set('sport_id', $this->sport->id)
        ->set('category_id', null)
        ->call('save')
        ->assertHasNoErrors();

    $team->refresh();

    expect($team->name)->toBe('Blue Wolves')
        ->and($team->short_name)->toBe('BW')
        ->and($team->category_id)->toBeNull();
});

test('team category must belong to selected sport when category has sport', function () {
    $otherSport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'other-sport',
    ]);

    $otherSportCategory = Category::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $otherSport->id,
    ]);

    Livewire::test(TeamCreate::class)
        ->set('name', 'Invalid Team')
        ->set('short_name', 'IT')
        ->set('sport_id', $this->sport->id)
        ->set('category_id', $otherSportCategory->id)
        ->call('save')
        ->assertHasErrors(['category_id']);
});

test('player can be created and updated', function () {
    Livewire::test(PlayerCreate::class)
        ->set('first_name', 'Alex')
        ->set('last_name', 'Stone')
        ->set('number', 1)
        ->call('save')
        ->assertHasNoErrors();

    $player = Player::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($player->first_name)->toBe('Alex')
        ->and($player->last_name)->toBe('Stone');

    Livewire::test(PlayerEdit::class, ['player' => $player])
        ->set('first_name', 'Alec')
        ->set('last_name', 'Stone')
        ->set('number', 2)
        ->call('save')
        ->assertHasNoErrors();

    $player->refresh();

    expect($player->first_name)->toBe('Alec')
        ->and($player->number)->toBe(2);
});

test('team player assignment can be created and updated with jersey number', function () {
    $team = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => null,
    ]);

    $player = Player::factory()->create([
        'organization_id' => $this->organization->id,
        'team_id' => null,
        'number' => 99,
    ]);

    Livewire::test(TeamPlayers::class, ['team' => $team])
        ->set('player_id', $player->id)
        ->set('jersey_number', 9)
        ->call('assignPlayer')
        ->assertHasNoErrors()
        ->set('jerseyNumbers.'.$player->id, 10)
        ->call('updateJerseyNumber', $player->id)
        ->assertHasNoErrors();

    $pivot = $team->players()->whereKey($player->id)->firstOrFail()->pivot;

    expect((int) $pivot->jersey_number)->toBe(10);
});
