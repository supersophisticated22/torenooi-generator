<?php

declare(strict_types=1);

use App\Livewire\Sports\Create;
use App\Livewire\Sports\Edit;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\User;
use Livewire\Livewire;

function tenantUser(): User
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach($organization->id);
    $user->update(['current_organization_id' => $organization->id]);

    return $user->fresh();
}

test('sport can be created with sport rule', function () {
    $user = tenantUser();

    $this->actingAs($user);

    $response = Livewire::test(Create::class)
        ->set('name', 'Football')
        ->set('win_points', 3)
        ->set('draw_points', 1)
        ->set('loss_points', 0)
        ->call('save');

    $response->assertHasNoErrors();

    $sport = Sport::query()->where('organization_id', $user->current_organization_id)->first();

    expect($sport)->not->toBeNull()
        ->and($sport->name)->toBe('Football')
        ->and($sport->sportRule)->not->toBeNull()
        ->and($sport->sportRule->win_points)->toBe(3)
        ->and($sport->sportRule->draw_points)->toBe(1)
        ->and($sport->sportRule->loss_points)->toBe(0);
});

test('sport and sport rule can be updated', function () {
    $user = tenantUser();

    $sport = Sport::factory()->create([
        'organization_id' => $user->current_organization_id,
        'name' => 'Initial Sport',
        'slug' => 'initial-sport',
    ]);

    $sport->sportRule()->create([
        'organization_id' => $user->current_organization_id,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Edit::class, ['sport' => $sport])
        ->set('name', 'Updated Sport')
        ->set('win_points', 4)
        ->set('draw_points', 2)
        ->set('loss_points', 1)
        ->call('save');

    $response->assertHasNoErrors();

    $sport->refresh();

    expect($sport->name)->toBe('Updated Sport')
        ->and($sport->sportRule)->not->toBeNull()
        ->and($sport->sportRule->win_points)->toBe(4)
        ->and($sport->sportRule->draw_points)->toBe(2)
        ->and($sport->sportRule->loss_points)->toBe(1);
});
