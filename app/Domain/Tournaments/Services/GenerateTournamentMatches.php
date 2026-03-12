<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Services;

use App\Domain\Scheduling\DTOs\ScheduledMatchDto;
use App\Domain\Scheduling\MatchScheduler;
use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Exceptions\TournamentMatchesAlreadyGeneratedException;
use App\Domain\Tournaments\TournamentEngine;
use App\Models\Field;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GenerateTournamentMatches
{
    public function __construct(
        private TournamentEngine $tournamentEngine,
        private MatchScheduler $matchScheduler,
    ) {}

    /**
     * @return Collection<int, TournamentMatch>
     */
    public function handle(int|Tournament $tournament, bool $force = false): Collection
    {
        $tournament = $this->resolveTournament($tournament);

        if ($tournament->matches()->exists() && ! $force) {
            throw new TournamentMatchesAlreadyGeneratedException('Matches already exist for this tournament.');
        }

        return DB::transaction(function () use ($tournament, $force): Collection {
            if ($force) {
                $tournament->matches()->delete();
            }

            $teamIds = $tournament->entries()
                ->whereNotNull('team_id')
                ->pluck('team_id')
                ->all();

            $generatedMatches = $this->tournamentEngine->generate($tournament->type, $teamIds);
            $scheduledMatches = $this->scheduleIfConfigured($tournament, $generatedMatches);

            if ($scheduledMatches !== null) {
                foreach ($scheduledMatches as $scheduledMatch) {
                    $this->persistScheduledMatch($tournament, $scheduledMatch);
                }
            } else {
                foreach ($generatedMatches as $generatedMatch) {
                    $this->persistGeneratedMatch($tournament, $generatedMatch);
                }
            }

            return $tournament->matches()
                ->orderBy('round')
                ->orderBy('starts_at')
                ->orderBy('id')
                ->get();
        });
    }

    private function resolveTournament(int|Tournament $tournament): Tournament
    {
        if ($tournament instanceof Tournament) {
            return $tournament->loadMissing('entries');
        }

        return Tournament::query()
            ->with('entries')
            ->findOrFail($tournament);
    }

    /**
     * @param  array<int, GeneratedMatchDto>  $generatedMatches
     * @return array<int, ScheduledMatchDto>|null
     */
    private function scheduleIfConfigured(Tournament $tournament, array $generatedMatches): ?array
    {
        if (! $this->hasSchedulingConfiguration($tournament)) {
            return null;
        }

        $fields = Field::query()
            ->forOrganization($tournament->organization_id)
            ->get(['id', 'sport_id'])
            ->map(fn (Field $field): array => [
                'id' => $field->id,
                'sport_id' => $field->sport_id,
            ])
            ->all();

        return $this->matchScheduler->schedule($generatedMatches, [
            'start_at' => $tournament->scheduled_start_at,
            'match_length_minutes' => (int) $tournament->match_duration_minutes,
            'break_between_matches_minutes' => (int) $tournament->break_duration_minutes,
            'break_between_pool_and_final_rounds_minutes' => (int) ($tournament->final_break_minutes ?? 0),
            'fields' => $fields,
            'tournament_sport_id' => $tournament->sport_id,
        ]);
    }

    private function hasSchedulingConfiguration(Tournament $tournament): bool
    {
        return $tournament->scheduled_start_at !== null
            && $tournament->match_duration_minutes !== null
            && $tournament->break_duration_minutes !== null;
    }

    private function persistGeneratedMatch(Tournament $tournament, GeneratedMatchDto $generatedMatch): void
    {
        TournamentMatch::query()->create([
            'organization_id' => $tournament->organization_id,
            'tournament_id' => $tournament->id,
            'pool_id' => null,
            'home_team_id' => $this->toTeamId($generatedMatch->homeTeamId),
            'away_team_id' => $this->toTeamId($generatedMatch->awayTeamId),
            'field_id' => null,
            'referee_id' => null,
            'starts_at' => null,
            'ends_at' => null,
            'round' => $generatedMatch->round,
            'status' => MatchStatus::Scheduled,
        ]);
    }

    private function persistScheduledMatch(Tournament $tournament, ScheduledMatchDto $scheduledMatch): void
    {
        TournamentMatch::query()->create([
            'organization_id' => $tournament->organization_id,
            'tournament_id' => $tournament->id,
            'pool_id' => null,
            'home_team_id' => $this->toTeamId($scheduledMatch->homeTeamId),
            'away_team_id' => $this->toTeamId($scheduledMatch->awayTeamId),
            'field_id' => (int) $scheduledMatch->fieldId,
            'referee_id' => null,
            'starts_at' => $scheduledMatch->startsAt,
            'ends_at' => $scheduledMatch->endsAt,
            'round' => $scheduledMatch->round,
            'status' => MatchStatus::Scheduled,
        ]);
    }

    private function toTeamId(int|string $teamId): ?int
    {
        if (is_int($teamId)) {
            return $teamId;
        }

        return null;
    }
}
