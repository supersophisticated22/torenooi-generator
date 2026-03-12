<?php

namespace App\Livewire\Teams;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Assign players')]
class Players extends Component
{
    #[Locked]
    public int $teamId;

    public ?int $player_id = null;

    public ?int $jersey_number = null;

    /**
     * @var array<int, int|null>
     */
    public array $jerseyNumbers = [];

    public function mount(Team $team): void
    {
        Gate::authorize('manage-tenant-record', $team);

        $this->teamId = $team->id;
    }

    public function assignPlayer(): void
    {
        $organization = Auth::user()?->currentOrganization();
        $team = Team::query()->findOrFail($this->teamId);

        Gate::authorize('manage-tenant-record', $team);

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'player_id' => ['required', Rule::exists('players', 'id')->where('organization_id', $organization->id)],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        if ($team->players()->whereKey($validated['player_id'])->exists()) {
            $this->addError('player_id', 'Player is already assigned to this team.');

            return;
        }

        if ($validated['jersey_number'] !== null
            && $team->players()->wherePivot('jersey_number', $validated['jersey_number'])->exists()) {
            $this->addError('jersey_number', 'Jersey number is already used by this team.');

            return;
        }

        $team->players()->attach($validated['player_id'], [
            'organization_id' => $organization->id,
            'jersey_number' => $validated['jersey_number'],
        ]);

        $this->player_id = null;
        $this->jersey_number = null;
    }

    public function updateJerseyNumber(int $playerId): void
    {
        $team = Team::query()->findOrFail($this->teamId);
        Gate::authorize('manage-tenant-record', $team);

        $jerseyNumber = $this->normalizeJerseyNumber($this->jerseyNumbers[$playerId] ?? null);

        if ($jerseyNumber !== null && $jerseyNumber < 0) {
            $this->addError('jerseyNumbers.'.$playerId, 'Jersey number must be at least 0.');

            return;
        }

        if ($jerseyNumber !== null
            && $team->players()
                ->whereKeyNot($playerId)
                ->wherePivot('jersey_number', $jerseyNumber)
                ->exists()) {
            $this->addError('jerseyNumbers.'.$playerId, 'Jersey number is already used by this team.');

            return;
        }

        $team->players()->updateExistingPivot($playerId, [
            'jersey_number' => $jerseyNumber,
        ]);
    }

    public function removePlayer(int $playerId): void
    {
        $team = Team::query()->findOrFail($this->teamId);
        Gate::authorize('manage-tenant-record', $team);

        $team->players()->detach($playerId);
    }

    #[Computed]
    public function team(): Team
    {
        return Team::query()->with(['players' => fn ($query) => $query->orderBy('players.first_name')])->findOrFail($this->teamId);
    }

    #[Computed]
    public function availablePlayers()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Player::query()
            ->forOrganization($organization)
            ->whereNotIn('id', $this->team->players->pluck('id')->all())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function normalizeJerseyNumber(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
