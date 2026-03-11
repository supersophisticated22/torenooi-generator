<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Generators;

use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use App\Domain\Tournaments\TournamentGenerator;

class FullCompetitionGenerator implements TournamentGenerator
{
    /**
     * @param  array<int, int|string>  $teamIds
     * @return array<int, GeneratedMatchDto>
     */
    public function generate(array $teamIds): array
    {
        $halfCompetitionGenerator = new HalfCompetitionGenerator;
        $firstLegMatches = $halfCompetitionGenerator->generate($teamIds);

        if ($firstLegMatches === []) {
            return [];
        }

        $roundOffset = max(array_map(
            fn (GeneratedMatchDto $match): int => $match->round,
            $firstLegMatches,
        ));

        $secondLegMatches = [];
        $matchNumber = count($firstLegMatches) + 1;

        foreach ($firstLegMatches as $match) {
            $secondLegMatches[] = new GeneratedMatchDto(
                matchNumber: $matchNumber,
                homeTeamId: $match->awayTeamId,
                awayTeamId: $match->homeTeamId,
                round: $match->round + $roundOffset,
                slot: $match->slot,
                homeSourceMatchNumber: $match->matchNumber,
                awaySourceMatchNumber: $match->matchNumber,
            );
            $matchNumber++;
        }

        return [...$firstLegMatches, ...$secondLegMatches];
    }
}
