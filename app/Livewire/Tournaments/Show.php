<?php

namespace App\Livewire\Tournaments;

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Exceptions\TournamentMatchesAlreadyGeneratedException;
use App\Domain\Tournaments\Services\GenerateTournamentMatches;
use App\Models\Referee;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Tournament details')]
class Show extends Component
{
    #[Locked]
    public int $tournamentId;

    #[Url(as: 'tab', except: 'settings')]
    public string $tab = 'settings';

    public ?int $tournament_referee_id = null;

    public bool $forceRegenerate = false;

    public function mount(Tournament $tournament): void
    {
        Gate::authorize('manage-tenant-record', $tournament);

        $this->tournamentId = $tournament->id;

        if (! in_array($this->tab, $this->tabs(), true)) {
            $this->tab = 'settings';
        }
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, $this->tabs(), true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function generateMatches(GenerateTournamentMatches $generateTournamentMatches): void
    {
        $tournament = Tournament::query()->findOrFail($this->tournamentId);
        Gate::authorize('manage-tenant-record', $tournament);

        try {
            $generateTournamentMatches->handle($tournament, $this->forceRegenerate);
            session()->flash('status', 'Matches generated successfully.');
        } catch (TournamentMatchesAlreadyGeneratedException) {
            $this->addError('generate', 'Matches already exist. Enable force regenerate to replace them.');
        }
    }

    public function assignReferee(): void
    {
        $organization = Auth::user()?->currentOrganization();
        $tournament = Tournament::query()->with(['sport', 'referees'])->findOrFail($this->tournamentId);

        Gate::authorize('manage-tenant-record', $tournament);

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'tournament_referee_id' => ['required', Rule::exists('referees', 'id')->where('organization_id', $organization->id)],
        ]);

        if ($tournament->referees()->whereKey($validated['tournament_referee_id'])->exists()) {
            $this->addError('tournament_referee_id', 'Referee is already assigned to this tournament.');

            return;
        }

        $referee = Referee::query()->forOrganization($organization)->findOrFail($validated['tournament_referee_id']);

        if ($referee->sport_id !== null && (int) $referee->sport_id !== (int) $tournament->sport_id) {
            $this->addError('tournament_referee_id', 'Referee sport does not match this tournament sport.');

            return;
        }

        $tournament->referees()->attach($referee->id, [
            'organization_id' => $organization->id,
        ]);

        $this->tournament_referee_id = null;
    }

    public function removeReferee(int $refereeId): void
    {
        $tournament = Tournament::query()->findOrFail($this->tournamentId);
        Gate::authorize('manage-tenant-record', $tournament);

        $tournament->referees()->detach($refereeId);
    }

    #[Computed]
    public function tournament(): Tournament
    {
        return Tournament::query()
            ->with([
                'event',
                'sport.sportRule',
                'category',
                'entries.team',
                'referees.sport',
                'matches.homeTeam',
                'matches.awayTeam',
                'matches.field.venue',
                'matches.referees',
                'matches.result',
            ])
            ->findOrFail($this->tournamentId);
    }

    #[Computed]
    public function availableReferees()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Referee::query()
            ->forOrganization($organization)
            ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $this->tournament->sport_id))
            ->whereNotIn('id', $this->tournament->referees->pluck('id')->all())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function standingsRows(): array
    {
        $rows = app(StandingsRecalculationService::class)
            ->recalculateForTournament($this->tournament);

        $teamIds = array_values(array_unique(array_map(fn (array $row): int|string => $row['team_id'], $rows)));

        $teamNames = Team::query()
            ->where('organization_id', $this->tournament->organization_id)
            ->whereIn('id', $teamIds)
            ->pluck('name', 'id');

        return array_map(function (array $row) use ($teamNames): array {
            $row['team_name'] = $teamNames->get($row['team_id'], (string) $row['team_id']);

            return $row;
        }, $rows);
    }

    /**
     * @return array<int, string>
     */
    public function tabs(): array
    {
        return ['settings', 'entries', 'referees', 'matches', 'standings', 'scores'];
    }

    public function render()
    {
        return view('livewire.tournaments.show');
    }
}
