<?php

namespace App\Livewire\Venues;

use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create venue')]
class Create extends Component
{
    public string $name = '';

    public ?string $address = null;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Venue::class);
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Venue::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'address' => $validated['address'],
        ]);

        $this->redirect(route('venues.index', absolute: false));
    }
}
