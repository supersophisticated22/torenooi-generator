<?php

namespace App\Livewire\Tournaments;

use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tournaments')]
class Index extends Component
{
    public function deleteTournament(int $tournamentId): void
    {
        $tournament = Tournament::query()->withCount(['entries', 'matches', 'pools'])->findOrFail($tournamentId);

        Gate::authorize('manage-tenant-record', $tournament);

        if ($tournament->entries_count > 0 || $tournament->matches_count > 0 || $tournament->pools_count > 0) {
            $this->addError('delete', 'This tournament is in use and cannot be deleted.');

            return;
        }

        $tournament->delete();
        session()->flash('status', 'Tournament deleted successfully.');
    }

    #[Computed]
    public function tournaments()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Tournament::query()
            ->forOrganization($organization)
            ->with(['event', 'sport', 'category'])
            ->orderByDesc('created_at')
            ->get();
    }
}
