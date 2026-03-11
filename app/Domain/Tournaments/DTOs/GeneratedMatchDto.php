<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\DTOs;

final readonly class GeneratedMatchDto
{
    public function __construct(
        public int $matchNumber,
        public int|string $homeTeamId,
        public int|string $awayTeamId,
        public int $round,
        public int $slot,
        public ?int $homeSourceMatchNumber = null,
        public ?int $awaySourceMatchNumber = null,
    ) {}

    /**
     * @return array{
     *     match_number:int,
     *     home_team_id:int|string,
     *     away_team_id:int|string,
     *     round:int,
     *     slot:int,
     *     home_source_match_number:?int,
     *     away_source_match_number:?int
     * }
     */
    public function toArray(): array
    {
        return [
            'match_number' => $this->matchNumber,
            'home_team_id' => $this->homeTeamId,
            'away_team_id' => $this->awayTeamId,
            'round' => $this->round,
            'slot' => $this->slot,
            'home_source_match_number' => $this->homeSourceMatchNumber,
            'away_source_match_number' => $this->awaySourceMatchNumber,
        ];
    }
}
