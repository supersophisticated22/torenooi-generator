<?php

declare(strict_types=1);

use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Events\Index as EventsIndex;
use App\Livewire\Fields\Index as FieldsIndex;
use App\Livewire\Players\Index as PlayersIndex;
use App\Livewire\Sports\Index as SportsIndex;
use App\Livewire\Teams\Index as TeamsIndex;
use App\Livewire\Tournaments\Index as TournamentsIndex;
use App\Livewire\Venues\Index as VenuesIndex;
use Illuminate\Contracts\View\View;

dataset('index-components', [
    [CategoriesIndex::class, 'livewire.categories.index'],
    [EventsIndex::class, 'livewire.events.index'],
    [FieldsIndex::class, 'livewire.fields.index'],
    [PlayersIndex::class, 'livewire.players.index'],
    [SportsIndex::class, 'livewire.sports.index'],
    [TeamsIndex::class, 'livewire.teams.index'],
    [TournamentsIndex::class, 'livewire.tournaments.index'],
    [VenuesIndex::class, 'livewire.venues.index'],
]);

it('renders index components using nested index views', function (string $componentClass, string $viewName): void {
    $component = new $componentClass;
    $view = $component->render();

    expect($view)->toBeInstanceOf(View::class)
        ->and($view->name())->toBe($viewName);
})->with('index-components');
