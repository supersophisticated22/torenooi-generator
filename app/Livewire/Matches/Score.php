<?php

namespace App\Livewire\Matches;

use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Enums\MatchEventType;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\MatchEvent;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Match score entry')]
class Score extends Component
{
    #[Locked]
    public int $matchId;

    public int $home_score = 0;

    public int $away_score = 0;

    public string $event_type = 'goal';

    public ?int $team_id = null;

    public ?int $minute = null;

    public ?string $notes = null;

    public function mount(TournamentMatch $match): void
    {
        Gate::authorize('manage-tenant-record', $match);

        $this->matchId = $match->id;
        $this->home_score = $match->result?->home_score ?? 0;
        $this->away_score = $match->result?->away_score ?? 0;
    }

    public function saveScore(): void
    {
        $match = TournamentMatch::query()->with('result')->findOrFail($this->matchId);

        Gate::authorize('manage-tenant-record', $match);

        $validated = $this->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:999'],
            'away_score' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $winnerTeamId = null;

        if ($validated['home_score'] > $validated['away_score']) {
            $winnerTeamId = $match->home_team_id;
        }

        if ($validated['away_score'] > $validated['home_score']) {
            $winnerTeamId = $match->away_team_id;
        }

        $match->result()->updateOrCreate(
            ['match_id' => $match->id],
            [
                'organization_id' => $match->organization_id,
                'home_score' => $validated['home_score'],
                'away_score' => $validated['away_score'],
                'winner_team_id' => $winnerTeamId,
                'notes' => null,
            ],
        );

        session()->flash('status', 'Score saved.');
    }

    public function addEvent(): void
    {
        $organization = Auth::user()?->currentOrganization();
        $match = TournamentMatch::query()->with(['homeTeam', 'awayTeam'])->findOrFail($this->matchId);

        Gate::authorize('manage-tenant-record', $match);

        if ($organization === null) {
            abort(403);
        }

        $teamIds = array_values(array_filter([$match->home_team_id, $match->away_team_id]));

        $validated = $this->validate([
            'event_type' => ['required', 'in:'.implode(',', array_column(MatchEventType::cases(), 'value'))],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('organization_id', $organization->id),
                Rule::in($teamIds),
            ],
            'minute' => ['nullable', 'integer', 'min:0', 'max:300'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        MatchEvent::query()->create([
            'organization_id' => $organization->id,
            'match_id' => $match->id,
            'team_id' => $validated['team_id'],
            'player_id' => null,
            'event_type' => $validated['event_type'],
            'minute' => $validated['minute'],
            'occurred_at' => null,
            'notes' => $validated['notes'],
        ]);

        $this->team_id = null;
        $this->minute = null;
        $this->notes = null;

        session()->flash('status', 'Match event added.');
    }

    public function completeMatch(StandingsRecalculationService $standingsRecalculationService): void
    {
        $match = TournamentMatch::query()->with(['result', 'tournament'])->findOrFail($this->matchId);

        Gate::authorize('manage-tenant-record', $match);

        if ($match->result === null) {
            $this->addError('result', 'Enter the match score before completing the match.');

            return;
        }

        $match->update([
            'status' => MatchStatus::Completed,
            'ends_at' => $match->ends_at ?? now(),
        ]);

        $standingsRecalculationService->recalculateForTournament($match->tournament);

        session()->flash('status', 'Match marked as completed.');
    }

    #[Computed]
    public function match(): TournamentMatch
    {
        return TournamentMatch::query()
            ->with([
                'tournament',
                'homeTeam',
                'awayTeam',
                'result',
                'events.team',
            ])
            ->findOrFail($this->matchId);
    }

    public function eventTypeOptions(): array
    {
        return array_map(fn (MatchEventType $eventType): array => [
            'value' => $eventType->value,
            'label' => ucfirst(str_replace('_', ' ', $eventType->value)),
        ], MatchEventType::cases());
    }

    public function teamOptions(): array
    {
        return collect([$this->match->homeTeam, $this->match->awayTeam])
            ->filter()
            ->map(fn ($team): array => [
                'value' => $team->id,
                'label' => $team->name,
            ])
            ->values()
            ->all();
    }
}
