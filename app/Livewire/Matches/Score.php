<?php

namespace App\Livewire\Matches;

use App\Domain\Scheduling\RefereeAssignmentValidator;
use App\Domain\Standings\StandingsRecalculationService;
use App\Domain\Tournaments\Enums\MatchEventType;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\Referee;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Builder;
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

    public ?int $player_id = null;

    public ?int $minute = null;

    public ?int $sequence = null;

    public ?string $notes = null;

    public ?int $referee_id = null;

    public function mount(TournamentMatch $match): void
    {
        Gate::authorize('view-tenant-record', $match);

        $this->matchId = $match->id;
        $this->home_score = $match->result?->home_score ?? 0;
        $this->away_score = $match->result?->away_score ?? 0;
    }

    public function saveScore(): void
    {
        $match = TournamentMatch::query()->with('result')->findOrFail($this->matchId);

        Gate::authorize('manage-match-scoring', $match);

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

        Gate::authorize('manage-match-scoring', $match);

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
            'player_id' => [
                'nullable',
                Rule::exists('players', 'id')->where('organization_id', $organization->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($teamIds): void {
                    if ($value === null || $value === '' || $teamIds === []) {
                        return;
                    }

                    $isEligible = Player::query()
                        ->whereKey((int) $value)
                        ->where(function (Builder $query) use ($teamIds): void {
                            $query
                                ->whereIn('team_id', $teamIds)
                                ->orWhereHas('teams', fn (Builder $teamQuery): Builder => $teamQuery->whereIn('teams.id', $teamIds));
                        })
                        ->exists();

                    if (! $isEligible) {
                        $fail('The selected player is not part of this match.');
                    }
                },
            ],
            'minute' => ['nullable', 'integer', 'min:0', 'max:300'],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        MatchEvent::query()->create([
            'organization_id' => $organization->id,
            'match_id' => $match->id,
            'team_id' => $validated['team_id'],
            'player_id' => $validated['player_id'],
            'event_type' => $validated['event_type'],
            'minute' => $validated['minute'],
            'sequence' => $validated['sequence'],
            'occurred_at' => null,
            'notes' => $validated['notes'],
            'metadata' => null,
        ]);

        $this->team_id = null;
        $this->player_id = null;
        $this->minute = null;
        $this->sequence = null;
        $this->notes = null;

        session()->flash('status', 'Match event added.');
    }

    public function removeEvent(int $eventId): void
    {
        $match = TournamentMatch::query()->findOrFail($this->matchId);

        Gate::authorize('manage-match-scoring', $match);

        MatchEvent::query()
            ->where('id', $eventId)
            ->where('match_id', $match->id)
            ->delete();

        session()->flash('status', 'Match event removed.');
    }

    public function assignReferee(RefereeAssignmentValidator $refereeAssignmentValidator): void
    {
        $organization = Auth::user()?->currentOrganization();
        $match = TournamentMatch::query()->with(['tournament', 'referees'])->findOrFail($this->matchId);

        Gate::authorize('manage-event-operations', $match);

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'referee_id' => ['required', Rule::exists('referees', 'id')->where('organization_id', $organization->id)],
        ]);

        $referee = Referee::query()->forOrganization($organization)->findOrFail($validated['referee_id']);
        $violations = $refereeAssignmentValidator->validateForMatch($match, $referee);

        if ($violations !== []) {
            $this->addError('referee_id', $violations[0]);

            return;
        }

        $match->referees()->attach($referee->id, [
            'organization_id' => $organization->id,
        ]);

        if ($match->referee_id === null) {
            $match->update(['referee_id' => $referee->id]);
        }

        $this->referee_id = null;
    }

    public function removeReferee(int $refereeId): void
    {
        $match = TournamentMatch::query()->with('referees')->findOrFail($this->matchId);
        Gate::authorize('manage-event-operations', $match);

        $match->referees()->detach($refereeId);

        if ((int) $match->referee_id === $refereeId) {
            $nextRefereeId = $match->referees()->orderBy('match_referee_assignments.id')->value('referees.id');
            $match->update(['referee_id' => $nextRefereeId]);
        }
    }

    public function completeMatch(StandingsRecalculationService $standingsRecalculationService): void
    {
        $match = TournamentMatch::query()->with(['result', 'tournament'])->findOrFail($this->matchId);

        Gate::authorize('manage-match-scoring', $match);

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
                'referees.sport',
                'events' => fn ($query) => $query
                    ->orderByRaw('COALESCE(sequence, 32767)')
                    ->orderByRaw('COALESCE(minute, 32767)')
                    ->orderBy('id')
                    ->with(['team', 'player']),
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

    public function availableReferees()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Referee::query()
            ->forOrganization($organization)
            ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $this->match->tournament->sport_id))
            ->whereNotIn('id', $this->match->referees->pluck('id')->all())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function playerOptions(): array
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return [];
        }

        $teamIds = array_values(array_filter([$this->match->home_team_id, $this->match->away_team_id]));

        if ($teamIds === []) {
            return [];
        }

        return Player::query()
            ->forOrganization($organization)
            ->where(function (Builder $query) use ($teamIds): void {
                $query
                    ->whereIn('team_id', $teamIds)
                    ->orWhereHas('teams', fn (Builder $teamQuery): Builder => $teamQuery->whereIn('teams.id', $teamIds));
            })
            ->orderBy('number')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Player $player): array => [
                'value' => $player->id,
                'label' => trim(
                    ($player->number !== null ? '#'.$player->number.' - ' : '')
                    .$player->first_name.' '.$player->last_name
                ),
            ])
            ->values()
            ->all();
    }
}
