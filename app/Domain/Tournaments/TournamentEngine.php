<?php

declare(strict_types=1);

namespace App\Domain\Tournaments;

use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Domain\Tournaments\Generators\FullCompetitionGenerator;
use App\Domain\Tournaments\Generators\HalfCompetitionGenerator;
use App\Domain\Tournaments\Generators\KnockoutGenerator;
use App\Domain\Tournaments\Generators\PlayoffGenerator;
use App\Domain\Tournaments\Generators\RankingGenerator;

class TournamentEngine
{
    public function resolveGenerator(TournamentType $type): TournamentGenerator
    {
        return match ($type) {
            TournamentType::HalfCompetition => new HalfCompetitionGenerator,
            TournamentType::FullCompetition => new FullCompetitionGenerator,
            TournamentType::Knockout => new KnockoutGenerator,
            TournamentType::Playoff => new PlayoffGenerator,
            TournamentType::Ranking => new RankingGenerator,
        };
    }

    /**
     * @param  array<int, int|string>  $teamIds
     * @return array<int, GeneratedMatchDto>
     */
    public function generate(TournamentType $type, array $teamIds): array
    {
        return $this->resolveGenerator($type)->generate($teamIds);
    }
}
