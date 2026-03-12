<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Teams')]
class Index extends Component
{
    public function deleteTeam(int $teamId): void
    {
        $team = Team::query()
            ->withCount(['homeMatches', 'awayMatches', 'tournamentEntries'])
            ->findOrFail($teamId);

        Gate::authorize('manage-tenant-record', $team);

        if ($team->home_matches_count > 0 || $team->away_matches_count > 0 || $team->tournament_entries_count > 0) {
            $this->addError('delete', 'This team is in use and cannot be deleted.');

            return;
        }

        $team->delete();
        session()->flash('status', 'Team deleted successfully.');
    }

    #[Computed]
    public function teams()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Team::query()
            ->forOrganization($organization)
            ->with(['sport', 'category'])
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.teams.index');
    }
}
