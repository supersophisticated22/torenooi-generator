<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Scheduling\DTOs\ScheduledMatchDto;
use App\Domain\Tournaments\DTOs\GeneratedMatchDto;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class MatchScheduler
{
    /**
     * @param  array<int, GeneratedMatchDto>  $matches
     * @param  array{
     *     start_at:DateTimeInterface|string,
     *     match_length_minutes:int,
     *     break_between_matches_minutes:int,
     *     break_between_pool_and_final_rounds_minutes?:int,
     *     final_round_start?:int|null,
     *     tournament_sport_id?:int|null,
     *     fields:array<int, array{id:int|string,sport_id?:int|null}>
     * }  $settings
     * @return array<int, ScheduledMatchDto>
     */
    public function schedule(array $matches, array $settings): array
    {
        if ($matches === []) {
            return [];
        }

        $startAt = $this->normalizeDateTime($settings['start_at']);
        $matchLengthMinutes = (int) $settings['match_length_minutes'];
        $breakBetweenMatchesMinutes = (int) $settings['break_between_matches_minutes'];
        $breakBetweenPoolAndFinalRoundsMinutes = (int) ($settings['break_between_pool_and_final_rounds_minutes'] ?? 0);
        $finalRoundStart = $settings['final_round_start'] ?? null;
        $tournamentSportId = $settings['tournament_sport_id'] ?? null;

        if ($matchLengthMinutes <= 0) {
            throw new InvalidArgumentException('match_length_minutes must be greater than zero.');
        }

        $availableFields = $this->filterCompatibleFields($settings['fields'] ?? [], $tournamentSportId);

        if ($availableFields === []) {
            throw new InvalidArgumentException('No compatible fields available for scheduling.');
        }

        usort($availableFields, fn (array $left, array $right): int => (string) $left['id'] <=> (string) $right['id']);

        usort($matches, function (GeneratedMatchDto $left, GeneratedMatchDto $right): int {
            return [$left->round, $left->slot, $left->matchNumber] <=> [$right->round, $right->slot, $right->matchNumber];
        });

        $fieldAvailableAt = [];
        foreach ($availableFields as $field) {
            $fieldAvailableAt[(string) $field['id']] = $startAt;
        }

        $teamAvailableAt = [];
        $scheduledMatches = [];

        $finalRoundBarrier = null;
        $finalBreakApplied = false;

        foreach ($matches as $match) {
            if ($finalRoundStart !== null && ! $finalBreakApplied && $match->round >= $finalRoundStart) {
                $latestFieldAvailability = $this->maxDateTime(array_values($fieldAvailableAt));
                $finalRoundBarrier = $latestFieldAvailability->addMinutes($breakBetweenPoolAndFinalRoundsMinutes);
                $finalBreakApplied = true;
            }

            $bestCandidate = null;

            foreach ($availableFields as $field) {
                $fieldKey = (string) $field['id'];
                $candidateStart = $fieldAvailableAt[$fieldKey];

                $homeTeamKey = (string) $match->homeTeamId;
                $awayTeamKey = (string) $match->awayTeamId;

                if (isset($teamAvailableAt[$homeTeamKey]) && $candidateStart->lessThan($teamAvailableAt[$homeTeamKey])) {
                    $candidateStart = $teamAvailableAt[$homeTeamKey];
                }

                if (isset($teamAvailableAt[$awayTeamKey]) && $candidateStart->lessThan($teamAvailableAt[$awayTeamKey])) {
                    $candidateStart = $teamAvailableAt[$awayTeamKey];
                }

                if ($finalRoundBarrier !== null && $candidateStart->lessThan($finalRoundBarrier)) {
                    $candidateStart = $finalRoundBarrier;
                }

                $candidateEnd = $candidateStart->addMinutes($matchLengthMinutes);

                if ($bestCandidate === null
                    || $candidateStart->lessThan($bestCandidate['starts_at'])
                    || ($candidateStart->equalTo($bestCandidate['starts_at']) && (string) $field['id'] < (string) $bestCandidate['field_id'])
                ) {
                    $bestCandidate = [
                        'field_id' => $field['id'],
                        'starts_at' => $candidateStart,
                        'ends_at' => $candidateEnd,
                    ];
                }
            }

            if ($bestCandidate === null) {
                throw new InvalidArgumentException('Unable to find a valid schedule slot.');
            }

            $scheduledMatches[] = new ScheduledMatchDto(
                matchNumber: $match->matchNumber,
                homeTeamId: $match->homeTeamId,
                awayTeamId: $match->awayTeamId,
                round: $match->round,
                slot: $match->slot,
                fieldId: $bestCandidate['field_id'],
                startsAt: $bestCandidate['starts_at'],
                endsAt: $bestCandidate['ends_at'],
            );

            $fieldAvailableAt[(string) $bestCandidate['field_id']] = $bestCandidate['ends_at']
                ->addMinutes($breakBetweenMatchesMinutes);

            $teamAvailableAt[(string) $match->homeTeamId] = $bestCandidate['ends_at'];
            $teamAvailableAt[(string) $match->awayTeamId] = $bestCandidate['ends_at'];
        }

        return $scheduledMatches;
    }

    /**
     * @param  array<int, array{id:int|string,sport_id?:int|null}>  $fields
     * @return array<int, array{id:int|string,sport_id?:int|null}>
     */
    private function filterCompatibleFields(array $fields, ?int $tournamentSportId): array
    {
        if ($tournamentSportId === null) {
            return $fields;
        }

        return array_values(array_filter($fields, function (array $field) use ($tournamentSportId): bool {
            if (! array_key_exists('sport_id', $field) || $field['sport_id'] === null) {
                return true;
            }

            return (int) $field['sport_id'] === $tournamentSportId;
        }));
    }

    private function normalizeDateTime(DateTimeInterface|string $dateTime): CarbonImmutable
    {
        if ($dateTime instanceof DateTimeInterface) {
            return CarbonImmutable::instance($dateTime);
        }

        return CarbonImmutable::parse($dateTime);
    }

    /**
     * @param  array<int, CarbonImmutable>  $dates
     */
    private function maxDateTime(array $dates): CarbonImmutable
    {
        $max = $dates[0];

        foreach ($dates as $date) {
            if ($date->greaterThan($max)) {
                $max = $date;
            }
        }

        return $max;
    }
}
