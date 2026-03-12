<?php

namespace App\Livewire\Sports;

use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sports')]
class Index extends Component
{
    public function deleteSport(int $sportId): void
    {
        $sport = Sport::query()
            ->withCount(['tournaments', 'fields'])
            ->with('category')
            ->findOrFail($sportId);

        Gate::authorize('manage-tenant-record', $sport);

        if ($sport->tournaments_count > 0 || $sport->fields_count > 0 || $sport->categories()->exists()) {
            $this->addError('delete', 'This sport is in use and cannot be deleted.');

            return;
        }

        $sport->delete();

        session()->flash('status', 'Sport deleted successfully.');
    }

    #[Computed]
    public function sports()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Sport::query()
            ->forOrganization($organization)
            ->with('sportRule')
            ->orderBy('name')
            ->get();
    }
}
