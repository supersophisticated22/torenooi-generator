<?php

declare(strict_types=1);

use App\Domain\Standings\StandingsCalculator;

it('calculates a normal ranking table', function (): void {
    $calculator = new StandingsCalculator;

    $table = $calculator->calculate([
        ['home_team_id' => 1, 'away_team_id' => 2, 'home_score' => 2, 'away_score' => 0],
        ['home_team_id' => 1, 'away_team_id' => 3, 'home_score' => 1, 'away_score' => 0],
        ['home_team_id' => 2, 'away_team_id' => 3, 'home_score' => 2, 'away_score' => 1],
    ], ['win' => 3, 'draw' => 1, 'loss' => 0]);

    expect(array_column($table, 'team_id'))->toBe([1, 2, 3])
        ->and($table[0]['points'])->toBe(6)
        ->and($table[1]['points'])->toBe(3)
        ->and($table[2]['points'])->toBe(0);
});

it('handles draws correctly', function (): void {
    $calculator = new StandingsCalculator;

    $table = $calculator->calculate([
        ['home_team_id' => 10, 'away_team_id' => 20, 'home_score' => 1, 'away_score' => 1],
    ], ['win' => 3, 'draw' => 1, 'loss' => 0]);

    expect($table[0]['draws'])->toBe(1)
        ->and($table[1]['draws'])->toBe(1)
        ->and($table[0]['points'])->toBe(1)
        ->and($table[1]['points'])->toBe(1);
});

it('resolves tied points by goal difference', function (): void {
    $calculator = new StandingsCalculator;

    $table = $calculator->calculate([
        ['home_team_id' => 1, 'away_team_id' => 2, 'home_score' => 4, 'away_score' => 0],
        ['home_team_id' => 3, 'away_team_id' => 1, 'home_score' => 1, 'away_score' => 0],
        ['home_team_id' => 2, 'away_team_id' => 3, 'home_score' => 2, 'away_score' => 0],
    ], ['win' => 3, 'draw' => 1, 'loss' => 0]);

    expect($table[0]['team_id'])->toBe(1)
        ->and($table[1]['team_id'])->toBe(2)
        ->and($table[0]['points'])->toBe(3)
        ->and($table[1]['points'])->toBe(3)
        ->and($table[0]['goal_difference'])->toBeGreaterThan($table[1]['goal_difference']);
});

it('resolves tied points and goal difference by goals scored', function (): void {
    $calculator = new StandingsCalculator;

    $table = $calculator->calculate([
        ['home_team_id' => 1, 'away_team_id' => 2, 'home_score' => 2, 'away_score' => 0],
        ['home_team_id' => 3, 'away_team_id' => 1, 'home_score' => 3, 'away_score' => 0],
        ['home_team_id' => 2, 'away_team_id' => 3, 'home_score' => 2, 'away_score' => 0],
    ], ['win' => 3, 'draw' => 1, 'loss' => 0]);

    expect($table[0]['team_id'])->toBe(2)
        ->and($table[1]['team_id'])->toBe(1)
        ->and($table[0]['points'])->toBe(3)
        ->and($table[1]['points'])->toBe(3)
        ->and($table[0]['goal_difference'])->toBe($table[1]['goal_difference'])
        ->and($table[0]['goals_for'])->toBeGreaterThan($table[1]['goals_for']);
});
