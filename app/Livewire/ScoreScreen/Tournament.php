<?php

declare(strict_types=1);

namespace App\Livewire\ScoreScreen;

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\Field;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Tournament as TournamentModel;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Public Tournament')]
class Tournament extends Component
{
    public int $organizationId;

    public int $tournamentId;

    #[Url(as: 'field', except: null)]
    public ?int $field_id = null;

    public function mount(Organization $organization, TournamentModel $tournament): void
    {
        if ((int) $tournament->organization_id !== (int) $organization->id) {
            abort(404);
        }

        $this->organizationId = $organization->id;
        $this->tournamentId = $tournament->id;
    }

    #[Computed]
    public function tournament(): TournamentModel
    {
        return TournamentModel::query()
            ->where('organization_id', $this->organizationId)
            ->with(['event', 'sport', 'entries.team'])
            ->findOrFail($this->tournamentId);
    }

    #[Computed]
    public function fields(): Collection
    {
        return Field::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('matches', fn (Builder $query): Builder => $query->where('tournament_id', $this->tournamentId))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function teams(): Collection
    {
        $teamIds = $this->matchesQuery()
            ->get(['home_team_id', 'away_team_id'])
            ->flatMap(fn (TournamentMatch $match): array => [(int) $match->home_team_id, (int) $match->away_team_id])
            ->filter()
            ->unique()
            ->values();

        if ($teamIds->isEmpty() && $this->field_id === null) {
            return $this->tournament->entries
                ->pluck('team')
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        return Team::query()
            ->where('organization_id', $this->organizationId)
            ->whereIn('id', $teamIds)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function schedule(): Collection
    {
        return $this->matchesQuery()
            ->whereIn('status', [MatchStatus::Scheduled->value, MatchStatus::InProgress->value])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function results(): Collection
    {
        return $this->matchesQuery()
            ->where('status', MatchStatus::Completed->value)
            ->whereHas('result')
            ->orderByDesc('ends_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    #[Computed]
    public function standingsRows(): array
    {
        $rows = app(StandingsRecalculationService::class)->recalculateForTournament($this->tournament);
        $teamIds = array_values(array_unique(array_map(fn (array $row): int|string => $row['team_id'], $rows)));

        $teamNames = Team::query()
            ->where('organization_id', $this->organizationId)
            ->whereIn('id', $teamIds)
            ->pluck('name', 'id');

        return array_map(function (array $row) use ($teamNames): array {
            $row['team_name'] = $teamNames->get($row['team_id'], (string) $row['team_id']);

            return $row;
        }, $rows);
    }

    public function render()
    {
        return view('livewire.score-screen.tournament')
            ->layout('layouts.public', ['title' => 'Tournament']);
    }

    private function matchesQuery(): Builder
    {
        return TournamentMatch::query()
            ->where('organization_id', $this->organizationId)
            ->where('tournament_id', $this->tournamentId)
            ->with([
                'homeTeam',
                'awayTeam',
                'field.venue',
                'result',
            ])
            ->when($this->field_id !== null, fn (Builder $query): Builder => $query->where('field_id', $this->field_id));
    }
}
