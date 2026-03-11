<?php

declare(strict_types=1);

namespace App\Domain\Tournaments;

use App\Domain\Tournaments\DTOs\GeneratedMatchDto;

interface TournamentGenerator
{
    /**
     * @param  array<int, int|string>  $teamIds
     * @return array<int, GeneratedMatchDto>
     */
    public function generate(array $teamIds): array;
}
