<?php

namespace App\Livewire\Tournaments;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tournament entries')]
class Entries extends Component
{
    #[Locked]
    public int $tournamentId;

    public ?int $team_id = null;

    public ?int $seed = null;

    /**
     * @var array<int, int|null>
     */
    public array $seeds = [];

    public function mount(Tournament $tournament): void
    {
        Gate::authorize('manage-tenant-record', $tournament);

        $this->tournamentId = $tournament->id;
    }

    public function addTeam(): void
    {
        $organization = Auth::user()?->currentOrganization();
        $tournament = Tournament::query()->findOrFail($this->tournamentId);

        Gate::authorize('manage-tenant-record', $tournament);

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'team_id' => ['required', Rule::exists('teams', 'id')->where('organization_id', $organization->id)],
            'seed' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        if ($tournament->entries()->where('team_id', $validated['team_id'])->exists()) {
            $this->addError('team_id', 'This team is already entered in the tournament.');

            return;
        }

        $tournament->entries()->create([
            'organization_id' => $organization->id,
            'team_id' => $validated['team_id'],
            'player_id' => null,
            'seed' => $validated['seed'],
        ]);

        $this->team_id = null;
        $this->seed = null;
    }

    public function updateSeed(int $entryId): void
    {
        $entry = TournamentEntry::query()->findOrFail($entryId);
        Gate::authorize('manage-tenant-record', $entry);

        $seed = $this->seeds[$entryId] ?? null;

        if ($seed === '') {
            $seed = null;
        }

        if ($seed !== null && ((int) $seed < 1 || (int) $seed > 999)) {
            $this->addError('seeds.'.$entryId, 'Seed must be between 1 and 999.');

            return;
        }

        $entry->update([
            'seed' => $seed,
        ]);
    }

    public function removeEntry(int $entryId): void
    {
        $entry = TournamentEntry::query()->findOrFail($entryId);
        Gate::authorize('manage-tenant-record', $entry);

        $entry->delete();
    }

    #[Computed]
    public function tournament(): Tournament
    {
        return Tournament::query()
            ->with(['entries.team' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($this->tournamentId);
    }

    #[Computed]
    public function availableTeams()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Team::query()
            ->forOrganization($organization)
            ->where('sport_id', $this->tournament->sport_id)
            ->whereNotIn('id', $this->tournament->entries->pluck('team_id')->all())
            ->orderBy('name')
            ->get();
    }
}
