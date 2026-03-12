<?php

namespace App\Livewire\Players;

use App\Models\Player;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Players')]
class Index extends Component
{
    public function deletePlayer(int $playerId): void
    {
        $player = Player::query()->withCount(['tournamentEntries', 'matchEvents', 'teams'])->findOrFail($playerId);

        Gate::authorize('manage-tenant-record', $player);

        if ($player->tournament_entries_count > 0 || $player->match_events_count > 0 || $player->teams_count > 0) {
            $this->addError('delete', 'This player is in use and cannot be deleted.');

            return;
        }

        $player->delete();
        session()->flash('status', 'Player deleted successfully.');
    }

    #[Computed]
    public function players()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Player::query()
            ->forOrganization($organization)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
