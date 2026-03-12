<?php

namespace App\Livewire\Venues;

use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Venues')]
class Index extends Component
{
    public function deleteVenue(int $venueId): void
    {
        $venue = Venue::query()->withCount('fields')->findOrFail($venueId);

        Gate::authorize('manage-tenant-record', $venue);

        if ($venue->fields_count > 0) {
            $this->addError('delete', 'This venue has fields and cannot be deleted.');

            return;
        }

        $venue->delete();
        session()->flash('status', 'Venue deleted successfully.');
    }

    #[Computed]
    public function venues()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Venue::query()
            ->forOrganization($organization)
            ->orderBy('name')
            ->get();
    }
}
