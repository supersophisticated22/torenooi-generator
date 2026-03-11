<?php

declare(strict_types=1);

namespace App\Domain\Standings;

use InvalidArgumentException;

class StandingsCalculator
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_TIE_BREAKERS = ['points', 'goal_difference', 'goals_for'];

    /**
     * @param  array<int, array{home_team_id:int|string,away_team_id:int|string,home_score:int,away_score:int,played?:bool}>  $matches
     * @param  array{win:int,draw:int,loss:int}  $points
     * @param  array<int|string, int|string>  $teamIds
     * @param  array<int, string>  $tieBreakers
     * @return array<int, array{team_id:int|string,played:int,wins:int,draws:int,losses:int,goals_for:int,goals_against:int,goal_difference:int,points:int}>
     */
    public function calculate(
        array $matches,
        array $points,
        array $teamIds = [],
        array $tieBreakers = self::DEFAULT_TIE_BREAKERS,
    ): array {
        $this->assertPointsConfig($points);

        $rows = [];

        foreach ($teamIds as $teamId) {
            $rows[(string) $teamId] = $this->emptyRow($teamId);
        }

        foreach ($matches as $match) {
            if (($match['played'] ?? true) === false) {
                continue;
            }

            $homeTeamId = $match['home_team_id'];
            $awayTeamId = $match['away_team_id'];

            if ($homeTeamId === $awayTeamId) {
                throw new InvalidArgumentException('A match cannot contain the same team twice.');
            }

            $homeKey = (string) $homeTeamId;
            $awayKey = (string) $awayTeamId;

            if (! isset($rows[$homeKey])) {
                $rows[$homeKey] = $this->emptyRow($homeTeamId);
            }

            if (! isset($rows[$awayKey])) {
                $rows[$awayKey] = $this->emptyRow($awayTeamId);
            }

            $homeScore = $match['home_score'];
            $awayScore = $match['away_score'];

            $rows[$homeKey]['played']++;
            $rows[$awayKey]['played']++;

            $rows[$homeKey]['goals_for'] += $homeScore;
            $rows[$homeKey]['goals_against'] += $awayScore;

            $rows[$awayKey]['goals_for'] += $awayScore;
            $rows[$awayKey]['goals_against'] += $homeScore;

            if ($homeScore > $awayScore) {
                $rows[$homeKey]['wins']++;
                $rows[$homeKey]['points'] += $points['win'];

                $rows[$awayKey]['losses']++;
                $rows[$awayKey]['points'] += $points['loss'];

                continue;
            }

            if ($homeScore < $awayScore) {
                $rows[$awayKey]['wins']++;
                $rows[$awayKey]['points'] += $points['win'];

                $rows[$homeKey]['losses']++;
                $rows[$homeKey]['points'] += $points['loss'];

                continue;
            }

            $rows[$homeKey]['draws']++;
            $rows[$awayKey]['draws']++;
            $rows[$homeKey]['points'] += $points['draw'];
            $rows[$awayKey]['points'] += $points['draw'];
        }

        $table = array_values(array_map(function (array $row): array {
            $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];

            return $row;
        }, $rows));

        usort($table, fn (array $left, array $right): int => $this->compareRows($left, $right, $tieBreakers));

        return $table;
    }

    /**
     * @param  array{win:int,draw:int,loss:int}  $points
     */
    private function assertPointsConfig(array $points): void
    {
        if (! isset($points['win'], $points['draw'], $points['loss'])) {
            throw new InvalidArgumentException('Points config must contain win, draw, and loss keys.');
        }
    }

    /**
     * @return array{team_id:int|string,played:int,wins:int,draws:int,losses:int,goals_for:int,goals_against:int,goal_difference:int,points:int}
     */
    private function emptyRow(int|string $teamId): array
    {
        return [
            'team_id' => $teamId,
            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0,
        ];
    }

    /**
     * @param  array{team_id:int|string,played:int,wins:int,draws:int,losses:int,goals_for:int,goals_against:int,goal_difference:int,points:int}  $left
     * @param  array{team_id:int|string,played:int,wins:int,draws:int,losses:int,goals_for:int,goals_against:int,goal_difference:int,points:int}  $right
     * @param  array<int, string>  $tieBreakers
     */
    private function compareRows(array $left, array $right, array $tieBreakers): int
    {
        foreach ($tieBreakers as $field) {
            if (! array_key_exists($field, $left) || ! array_key_exists($field, $right)) {
                continue;
            }

            if ($left[$field] === $right[$field]) {
                continue;
            }

            return $right[$field] <=> $left[$field];
        }

        return (string) $left['team_id'] <=> (string) $right['team_id'];
    }
}
