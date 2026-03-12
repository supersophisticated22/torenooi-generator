<?php

declare(strict_types=1);

use App\Domain\Scheduling\MatchScheduler;
use App\Domain\Tournaments\DTOs\GeneratedMatchDto;

it('schedules sequentially on one field', function (): void {
    $scheduler = new MatchScheduler;

    $matches = [
        new GeneratedMatchDto(matchNumber: 1, homeTeamId: 1, awayTeamId: 2, round: 1, slot: 1),
        new GeneratedMatchDto(matchNumber: 2, homeTeamId: 3, awayTeamId: 4, round: 1, slot: 2),
    ];

    $scheduled = $scheduler->schedule($matches, [
        'start_at' => '2026-03-11 09:00:00',
        'match_length_minutes' => 30,
        'break_between_matches_minutes' => 10,
        'fields' => [
            ['id' => 1],
        ],
    ]);

    expect($scheduled)->toHaveCount(2)
        ->and($scheduled[0]->fieldId)->toBe(1)
        ->and($scheduled[0]->startsAt->format('H:i'))->toBe('09:00')
        ->and($scheduled[1]->startsAt->format('H:i'))->toBe('09:40');
});

it('schedules in parallel on multiple fields', function (): void {
    $scheduler = new MatchScheduler;

    $matches = [
        new GeneratedMatchDto(matchNumber: 1, homeTeamId: 1, awayTeamId: 2, round: 1, slot: 1),
        new GeneratedMatchDto(matchNumber: 2, homeTeamId: 3, awayTeamId: 4, round: 1, slot: 2),
    ];

    $scheduled = $scheduler->schedule($matches, [
        'start_at' => '2026-03-11 09:00:00',
        'match_length_minutes' => 30,
        'break_between_matches_minutes' => 10,
        'fields' => [
            ['id' => 1],
            ['id' => 2],
        ],
    ]);

    expect($scheduled[0]->startsAt->format('H:i'))->toBe('09:00')
        ->and($scheduled[1]->startsAt->format('H:i'))->toBe('09:00')
        ->and($scheduled[0]->fieldId)->not->toBe($scheduled[1]->fieldId);
});

it('does not schedule overlapping matches for the same team', function (): void {
    $scheduler = new MatchScheduler;

    $matches = [
        new GeneratedMatchDto(matchNumber: 1, homeTeamId: 1, awayTeamId: 2, round: 1, slot: 1),
        new GeneratedMatchDto(matchNumber: 2, homeTeamId: 1, awayTeamId: 3, round: 1, slot: 2),
    ];

    $scheduled = $scheduler->schedule($matches, [
        'start_at' => '2026-03-11 09:00:00',
        'match_length_minutes' => 30,
        'break_between_matches_minutes' => 0,
        'fields' => [
            ['id' => 1],
            ['id' => 2],
        ],
    ]);

    expect($scheduled[0]->startsAt->format('H:i'))->toBe('09:00')
        ->and($scheduled[1]->startsAt->format('H:i'))->toBe('09:30');
});

it('respects field sport compatibility', function (): void {
    $scheduler = new MatchScheduler;

    $matches = [
        new GeneratedMatchDto(matchNumber: 1, homeTeamId: 1, awayTeamId: 2, round: 1, slot: 1),
        new GeneratedMatchDto(matchNumber: 2, homeTeamId: 3, awayTeamId: 4, round: 1, slot: 2),
    ];

    $scheduled = $scheduler->schedule($matches, [
        'start_at' => '2026-03-11 09:00:00',
        'match_length_minutes' => 30,
        'break_between_matches_minutes' => 0,
        'tournament_sport_id' => 10,
        'fields' => [
            ['id' => 1, 'sport_id' => 10],
            ['id' => 2, 'sport_id' => 20],
        ],
    ]);

    expect($scheduled)->toHaveCount(2)
        ->and($scheduled[0]->fieldId)->toBe(1)
        ->and($scheduled[1]->fieldId)->toBe(1);
});
