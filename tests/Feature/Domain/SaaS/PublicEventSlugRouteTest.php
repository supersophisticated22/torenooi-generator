<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\OrganizationRole;
use App\Livewire\Events\Create as EventCreate;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('serves public event page by organization slug and event slug', function (): void {
    $organization = Organization::factory()->create([
        'slug' => 'acme-sports',
    ]);

    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Acme Summer Cup',
        'slug' => 'acme-summer-cup',
    ]);

    $this->get(route('events.public.show', ['organization' => $organization->slug, 'eventSlug' => $event->slug]))
        ->assertOk()
        ->assertSee('Acme Summer Cup');
});

it('keeps public event slug resolution tenant scoped', function (): void {
    $organizationA = Organization::factory()->create(['slug' => 'org-a']);
    $organizationB = Organization::factory()->create(['slug' => 'org-b']);

    Event::factory()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Org A Event',
        'slug' => 'same-event-slug',
    ]);

    Event::factory()->create([
        'organization_id' => $organizationB->id,
        'name' => 'Org B Event',
        'slug' => 'same-event-slug',
    ]);

    $this->get(route('events.public.show', ['organization' => $organizationA->slug, 'eventSlug' => 'same-event-slug']))
        ->assertOk()
        ->assertSee('Org A Event')
        ->assertDontSee('Org B Event');
});

it('generates unique event slugs within one organization', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $user->organizations()->attach($organization->id, ['role' => OrganizationRole::OrganizationAdmin->value]);
    $user->update(['current_organization_id' => $organization->id]);

    $this->actingAs($user);

    Livewire::test(EventCreate::class)
        ->set('name', 'City Finals')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(EventCreate::class)
        ->set('name', 'City Finals')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    $slugs = Event::query()
        ->where('organization_id', $organization->id)
        ->where('name', 'City Finals')
        ->pluck('slug')
        ->sort()
        ->values()
        ->all();

    expect($slugs)->toBe(['city-finals', 'city-finals-2']);
});
