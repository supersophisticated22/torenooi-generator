<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Generators;

use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use App\Domain\Tournaments\TournamentGenerator;
use InvalidArgumentException;

class HalfCompetitionGenerator implements TournamentGenerator
{
    /**
     * @param  array<int, int|string>  $teamIds
     * @return array<int, GeneratedMatchDto>
     */
    public function generate(array $teamIds): array
    {
        if (count($teamIds) !== count(array_unique($teamIds, SORT_REGULAR))) {
            throw new InvalidArgumentException('Duplicate team IDs are not allowed.');
        }

        if (count($teamIds) < 2) {
            return [];
        }

        $rotation = array_values($teamIds);

        if ((count($rotation) % 2) !== 0) {
            $rotation[] = null;
        }

        $teamCount = count($rotation);
        $roundCount = $teamCount - 1;
        $matches = [];
        $matchNumber = 1;

        for ($round = 1; $round <= $roundCount; $round++) {
            for ($slot = 0; $slot < ($teamCount / 2); $slot++) {
                $homeTeamId = $rotation[$slot];
                $awayTeamId = $rotation[$teamCount - 1 - $slot];

                if ($homeTeamId === null || $awayTeamId === null) {
                    continue;
                }

                $matches[] = new GeneratedMatchDto(
                    matchNumber: $matchNumber,
                    homeTeamId: $homeTeamId,
                    awayTeamId: $awayTeamId,
                    round: $round,
                    slot: $slot + 1,
                );
                $matchNumber++;
            }

            $fixedTeam = $rotation[0];
            $lastTeam = array_pop($rotation);
            array_splice($rotation, 1, 0, [$lastTeam]);
            $rotation[0] = $fixedTeam;
        }

        return $matches;
    }
}
