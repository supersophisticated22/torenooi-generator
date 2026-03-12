<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\DTOs;

use Carbon\CarbonImmutable;

final readonly class ScheduledMatchDto
{
    public function __construct(
        public int $matchNumber,
        public int|string $homeTeamId,
        public int|string $awayTeamId,
        public int $round,
        public int $slot,
        public int|string $fieldId,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {}

    /**
     * @return array{
     *     match_number:int,
     *     home_team_id:int|string,
     *     away_team_id:int|string,
     *     round:int,
     *     slot:int,
     *     field_id:int|string,
     *     starts_at:string,
     *     ends_at:string
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
            'field_id' => $this->fieldId,
            'starts_at' => $this->startsAt->toIso8601String(),
            'ends_at' => $this->endsAt->toIso8601String(),
        ];
    }
}
