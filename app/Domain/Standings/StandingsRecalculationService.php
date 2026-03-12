<?php

declare(strict_types=1);

namespace App\Domain\Standings;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\Tournament;

class StandingsRecalculationService
{
    public function __construct(private StandingsCalculator $standingsCalculator) {}

    /**
     * @return array<int, array{team_id:int|string,played:int,wins:int,draws:int,losses:int,goals_for:int,goals_against:int,goal_difference:int,points:int}>
     */
    public function recalculateForTournament(Tournament $tournament): array
    {
        $tournament->loadMissing([
            'entries',
            'sport.sportRule',
            'matches.result',
        ]);

        $points = [
            'win' => $tournament->sport->sportRule?->win_points ?? 3,
            'draw' => $tournament->sport->sportRule?->draw_points ?? 1,
            'loss' => $tournament->sport->sportRule?->loss_points ?? 0,
        ];

        $teamIds = $tournament->entries
            ->pluck('team_id')
            ->filter(fn (?int $teamId): bool => $teamId !== null)
            ->values()
            ->all();

        $matches = $tournament->matches
            ->filter(fn ($match): bool => $match->status === MatchStatus::Completed
                && $match->result !== null
                && $match->home_team_id !== null
                && $match->away_team_id !== null)
            ->map(fn ($match): array => [
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'home_score' => $match->result->home_score,
                'away_score' => $match->result->away_score,
                'played' => true,
            ])
            ->values()
            ->all();

        return $this->standingsCalculator->calculate($matches, $points, $teamIds);
    }
}
