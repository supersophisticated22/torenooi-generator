<?php

namespace App\Livewire\Referees;

use App\Models\Referee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Referees')]
class Index extends Component
{
    public function deleteReferee(int $refereeId): void
    {
        $referee = Referee::query()->withCount(['tournaments', 'matches', 'primaryMatches'])->findOrFail($refereeId);

        Gate::authorize('manage-tenant-record', $referee);

        if ($referee->tournaments_count > 0 || $referee->matches_count > 0 || $referee->primary_matches_count > 0) {
            $this->addError('delete', 'This referee is assigned and cannot be deleted.');

            return;
        }

        $referee->delete();
        session()->flash('status', 'Referee deleted successfully.');
    }

    #[Computed]
    public function referees()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Referee::query()
            ->forOrganization($organization)
            ->with('sport')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.referees.index');
    }
}
