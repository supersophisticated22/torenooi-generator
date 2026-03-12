<?php

declare(strict_types=1);

use App\Livewire\Fields\Create as FieldCreate;
use App\Livewire\Fields\Edit as FieldEdit;
use App\Livewire\Venues\Create as VenueCreate;
use App\Livewire\Venues\Edit as VenueEdit;
use App\Models\Field;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();

    $this->user->organizations()->attach($this->organization->id);
    $this->user->update(['current_organization_id' => $this->organization->id]);

    $this->sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'venue-field-sport',
    ]);

    $this->actingAs($this->user);
});

test('venue can be created and updated', function () {
    Livewire::test(VenueCreate::class)
        ->set('name', 'Main Arena')
        ->set('address', '123 Center Street')
        ->call('save')
        ->assertHasNoErrors();

    $venue = Venue::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($venue->name)->toBe('Main Arena')
        ->and($venue->address)->toBe('123 Center Street');

    Livewire::test(VenueEdit::class, ['venue' => $venue])
        ->set('name', 'Secondary Arena')
        ->set('address', '321 Park Avenue')
        ->call('save')
        ->assertHasNoErrors();

    $venue->refresh();

    expect($venue->name)->toBe('Secondary Arena')
        ->and($venue->address)->toBe('321 Park Avenue');
});

test('field can be created and updated with optional sport', function () {
    $venue = Venue::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(FieldCreate::class)
        ->set('name', 'Court 1')
        ->set('code', 'C1')
        ->set('venue_id', $venue->id)
        ->set('sport_id', $this->sport->id)
        ->call('save')
        ->assertHasNoErrors();

    $field = Field::query()->where('organization_id', $this->organization->id)->firstOrFail();

    expect($field->name)->toBe('Court 1')
        ->and($field->code)->toBe('C1')
        ->and($field->venue_id)->toBe($venue->id)
        ->and($field->sport_id)->toBe($this->sport->id);

    Livewire::test(FieldEdit::class, ['field' => $field])
        ->set('name', 'Court 2')
        ->set('code', 'C2')
        ->set('venue_id', $venue->id)
        ->set('sport_id', null)
        ->call('save')
        ->assertHasNoErrors();

    $field->refresh();

    expect($field->name)->toBe('Court 2')
        ->and($field->code)->toBe('C2')
        ->and($field->sport_id)->toBeNull();
});
