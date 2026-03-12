<?php

declare(strict_types=1);

namespace App\Livewire\ScoreScreen;

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\Event as EventModel;
use App\Models\Field;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Public Event')]
class Event extends Component
{
    public int $organizationId;

    public int $eventId;

    #[Url(as: 'sport', except: null)]
    public ?int $sport_id = null;

    #[Url(as: 'tournament', except: null)]
    public ?int $tournament_id = null;

    #[Url(as: 'field', except: null)]
    public ?int $field_id = null;

    public function mount(Organization $organization, EventModel $event): void
    {
        if ((int) $event->organization_id !== (int) $organization->id) {
            abort(404);
        }

        $this->organizationId = $organization->id;
        $this->eventId = $event->id;
    }

    #[Computed]
    public function event(): EventModel
    {
        return EventModel::query()
            ->where('organization_id', $this->organizationId)
            ->with('tournaments.sport')
            ->findOrFail($this->eventId);
    }

    #[Computed]
    public function sports(): Collection
    {
        return Sport::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('tournaments', fn (Builder $query): Builder => $query->where('event_id', $this->eventId))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function tournaments(): Collection
    {
        return Tournament::query()
            ->where('organization_id', $this->organizationId)
            ->where('event_id', $this->eventId)
            ->when($this->sport_id !== null, fn (Builder $query): Builder => $query->where('sport_id', $this->sport_id))
            ->whereHas('matches')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function fields(): Collection
    {
        return Field::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('matches', function (Builder $query): Builder {
                return $query->whereHas('tournament', function (Builder $tournamentQuery): Builder {
                    return $tournamentQuery
                        ->where('event_id', $this->eventId)
                        ->when($this->sport_id !== null, fn (Builder $sportQuery): Builder => $sportQuery->where('sport_id', $this->sport_id))
                        ->when($this->tournament_id !== null, fn (Builder $singleQuery): Builder => $singleQuery->where('id', $this->tournament_id));
                });
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function teams(): Collection
    {
        $teamIds = $this->matchesQuery()
            ->get(['home_team_id', 'away_team_id'])
            ->flatMap(fn (TournamentMatch $match): array => [$match->home_team_id, $match->away_team_id])
            ->filter()
            ->unique()
            ->values();

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
        $tournament = $this->standingsTournament();

        if ($tournament === null) {
            return [];
        }

        $rows = app(StandingsRecalculationService::class)->recalculateForTournament($tournament);
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

    #[Computed]
    public function standingsTournamentName(): ?string
    {
        return $this->standingsTournament()?->name;
    }

    public function render()
    {
        return view('livewire.score-screen.event')
            ->layout('layouts.public', ['title' => 'Event']);
    }

    private function standingsTournament(): ?Tournament
    {
        if ($this->tournament_id !== null) {
            return Tournament::query()
                ->where('organization_id', $this->organizationId)
                ->where('event_id', $this->eventId)
                ->find($this->tournament_id);
        }

        return $this->tournaments->first();
    }

    private function matchesQuery(): Builder
    {
        return TournamentMatch::query()
            ->where('organization_id', $this->organizationId)
            ->with([
                'tournament.event',
                'tournament.sport',
                'homeTeam',
                'awayTeam',
                'field.venue',
                'result',
            ])
            ->whereHas('tournament', function (Builder $query): Builder {
                return $query
                    ->where('event_id', $this->eventId)
                    ->when($this->sport_id !== null, fn (Builder $sportQuery): Builder => $sportQuery->where('sport_id', $this->sport_id))
                    ->when($this->tournament_id !== null, fn (Builder $tournamentQuery): Builder => $tournamentQuery->where('id', $this->tournament_id));
            })
            ->when($this->field_id !== null, fn (Builder $query): Builder => $query->where('field_id', $this->field_id));
    }
}
