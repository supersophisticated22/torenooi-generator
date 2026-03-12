<?php

namespace App\Livewire\Tournaments;

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Exceptions\TournamentMatchesAlreadyGeneratedException;
use App\Domain\Tournaments\Services\GenerateTournamentMatches;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\Gate;
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

    #[Computed]
    public function tournament(): Tournament
    {
        return Tournament::query()
            ->with([
                'event',
                'sport.sportRule',
                'category',
                'entries.team',
                'matches.homeTeam',
                'matches.awayTeam',
                'matches.field.venue',
                'matches.result',
            ])
            ->findOrFail($this->tournamentId);
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
        return ['settings', 'entries', 'matches', 'standings', 'scores'];
    }

    public function render()
    {
        return view('livewire.tournaments.show');
    }
}
