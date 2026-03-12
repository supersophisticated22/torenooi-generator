<?php

namespace App\Livewire\Venues;

use App\Models\Venue;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit venue')]
class Edit extends Component
{
    #[Locked]
    public int $venueId;

    public string $name = '';

    public ?string $address = null;

    public function mount(Venue $venue): void
    {
        Gate::authorize('manage-tenant-record', $venue);

        $this->venueId = $venue->id;
        $this->name = $venue->name;
        $this->address = $venue->address;
    }

    public function save(): void
    {
        $venue = Venue::query()->findOrFail($this->venueId);
        Gate::authorize('manage-tenant-record', $venue);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $venue->update([
            'name' => $validated['name'],
            'address' => $validated['address'],
        ]);

        $this->redirect(route('venues.index', absolute: false));
    }
}
