<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Generators;

use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use App\Domain\Tournaments\TournamentGenerator;
use InvalidArgumentException;

class KnockoutGenerator implements TournamentGenerator
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

        $teamIds = array_values($teamIds);
        $bracketSize = $this->nextPowerOfTwo(count($teamIds));
        $seedPositions = $this->seedPositions($bracketSize);

        $seededSlots = array_map(function (int $seed) use ($teamIds): int|string {
            return $teamIds[$seed - 1] ?? 'BYE';
        }, $seedPositions);

        $matches = [];
        $matchNumber = 1;
        $round = 1;

        $roundMatchNumbers = [];
        for ($slot = 0; $slot < ($bracketSize / 2); $slot++) {
            $homeTeamId = $seededSlots[$slot * 2];
            $awayTeamId = $seededSlots[($slot * 2) + 1];

            $matches[] = new GeneratedMatchDto(
                matchNumber: $matchNumber,
                homeTeamId: $homeTeamId,
                awayTeamId: $awayTeamId,
                round: $round,
                slot: $slot + 1,
            );

            $roundMatchNumbers[] = $matchNumber;
            $matchNumber++;
        }

        $round++;

        while (count($roundMatchNumbers) > 1) {
            $nextRoundMatchNumbers = [];

            for ($slot = 0; $slot < (count($roundMatchNumbers) / 2); $slot++) {
                $homeSource = $roundMatchNumbers[$slot * 2];
                $awaySource = $roundMatchNumbers[($slot * 2) + 1];

                $matches[] = new GeneratedMatchDto(
                    matchNumber: $matchNumber,
                    homeTeamId: 'winner:'.$homeSource,
                    awayTeamId: 'winner:'.$awaySource,
                    round: $round,
                    slot: $slot + 1,
                    homeSourceMatchNumber: $homeSource,
                    awaySourceMatchNumber: $awaySource,
                );

                $nextRoundMatchNumbers[] = $matchNumber;
                $matchNumber++;
            }

            $roundMatchNumbers = $nextRoundMatchNumbers;
            $round++;
        }

        return $matches;
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;

        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }

    /**
     * @return array<int, int>
     */
    private function seedPositions(int $bracketSize): array
    {
        $positions = [1, 2];

        while (count($positions) < $bracketSize) {
            $nextBracketSize = count($positions) * 2;
            $nextPositions = [];

            foreach ($positions as $position) {
                $nextPositions[] = $position;
                $nextPositions[] = ($nextBracketSize + 1) - $position;
            }

            $positions = $nextPositions;
        }

        return $positions;
    }
}
