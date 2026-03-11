<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Generators;

use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use App\Domain\Tournaments\TournamentGenerator;

class RankingGenerator implements TournamentGenerator
{
    /**
     * @param  array<int, int|string>  $teamIds
     * @return array<int, GeneratedMatchDto>
     */
    public function generate(array $teamIds): array
    {
        return [];
    }
}
