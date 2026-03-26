<?php

declare(strict_types=1);

namespace App\Livewire\ScoreScreen;

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\Event;
use App\Models\Field;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Score Screen')]
class Index extends Component
{
    public int $organizationId;

    #[Url(as: 'event', except: null)]
    public ?int $event_id = null;

    #[Url(as: 'tournament', except: null)]
    public ?int $tournament_id = null;

    #[Url(as: 'venue', except: null)]
    public ?int $venue_id = null;

    #[Url(as: 'field', except: null)]
    public ?int $field_id = null;

    #[Url(as: 'sport', except: null)]
    public ?int $sport_id = null;

    public function mount(Organization $organization): void
    {
        $this->organizationId = $organization->id;
    }

    #[Computed]
    public function events(): Collection
    {
        return Event::query()
            ->where('organization_id', $this->organizationId)
            ->where('is_private', false)
            ->whereHas('tournaments.matches')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function sports(): Collection
    {
        return Sport::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('tournaments', function (Builder $query): Builder {
                return $query
                    ->whereHas('event', fn (Builder $eventQuery): Builder => $eventQuery->where('is_private', false))
                    ->whereHas('matches');
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function venues(): Collection
    {
        return Venue::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('fields.matches', function (Builder $query): Builder {
                return $query->whereHas('tournament.event', fn (Builder $eventQuery): Builder => $eventQuery->where('is_private', false));
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function fields(): Collection
    {
        return Field::query()
            ->where('organization_id', $this->organizationId)
            ->when($this->venue_id !== null, fn (Builder $query): Builder => $query->where('venue_id', $this->venue_id))
            ->whereHas('matches', function (Builder $query): Builder {
                return $query->whereHas('tournament.event', fn (Builder $eventQuery): Builder => $eventQuery->where('is_private', false));
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function tournaments(): Collection
    {
        return Tournament::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('event', fn (Builder $query): Builder => $query->where('is_private', false))
            ->when($this->event_id !== null, fn (Builder $query): Builder => $query->where('event_id', $this->event_id))
            ->when($this->sport_id !== null, fn (Builder $query): Builder => $query->where('sport_id', $this->sport_id))
            ->whereHas('matches')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function currentMatch(): ?TournamentMatch
    {
        $now = now();

        return $this->matchesQuery()
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->where('status', MatchStatus::InProgress->value)
                    ->orWhere(function (Builder $running) use ($now): void {
                        $running
                            ->whereIn('status', [MatchStatus::Scheduled->value, MatchStatus::InProgress->value])
                            ->whereNotNull('starts_at')
                            ->where('starts_at', '<=', $now)
                            ->where(function (Builder $window) use ($now): void {
                                $window
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $now);
                            });
                    });
            })
            ->orderByDesc('starts_at')
            ->first();
    }

    #[Computed]
    public function upcomingMatches(): Collection
    {
        return $this->matchesQuery()
            ->whereIn('status', [MatchStatus::Scheduled->value, MatchStatus::InProgress->value])
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function latestResults(): Collection
    {
        return $this->matchesQuery()
            ->where('status', MatchStatus::Completed->value)
            ->whereHas('result')
            ->orderByDesc('ends_at')
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function standings(): array
    {
        $tournament = $this->standingsTournament();

        if ($tournament === null) {
            return [];
        }

        return app(StandingsRecalculationService::class)->recalculateForTournament($tournament);
    }

    #[Computed]
    public function standingsRows(): array
    {
        $rows = $this->standings;
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
        return view('livewire.score-screen.index')
            ->layout('layouts.public', ['title' => 'Score Screen']);
    }

    private function standingsTournament(): ?Tournament
    {
        if ($this->tournament_id !== null) {
            return Tournament::query()
                ->where('organization_id', $this->organizationId)
                ->whereHas('event', fn (Builder $query): Builder => $query->where('is_private', false))
                ->find($this->tournament_id);
        }

        if ($this->currentMatch !== null) {
            return $this->currentMatch->tournament;
        }

        $latestResult = $this->latestResults->first();

        if ($latestResult !== null) {
            return $latestResult->tournament;
        }

        return null;
    }

    private function matchesQuery(): Builder
    {
        return TournamentMatch::query()
            ->where('organization_id', $this->organizationId)
            ->whereHas('tournament.event', fn (Builder $query): Builder => $query->where('is_private', false))
            ->with([
                'tournament.event',
                'tournament.sport',
                'homeTeam',
                'awayTeam',
                'field.venue',
                'result',
            ])
            ->when($this->tournament_id !== null, fn (Builder $query): Builder => $query->where('tournament_id', $this->tournament_id))
            ->when($this->event_id !== null, function (Builder $query): Builder {
                return $query->whereHas('tournament', fn (Builder $tournamentQuery): Builder => $tournamentQuery->where('event_id', $this->event_id));
            })
            ->when($this->sport_id !== null, function (Builder $query): Builder {
                return $query->whereHas('tournament', fn (Builder $tournamentQuery): Builder => $tournamentQuery->where('sport_id', $this->sport_id));
            })
            ->when($this->venue_id !== null, function (Builder $query): Builder {
                return $query->whereHas('field', fn (Builder $fieldQuery): Builder => $fieldQuery->where('venue_id', $this->venue_id));
            })
            ->when($this->field_id !== null, fn (Builder $query): Builder => $query->where('field_id', $this->field_id));
    }
}
