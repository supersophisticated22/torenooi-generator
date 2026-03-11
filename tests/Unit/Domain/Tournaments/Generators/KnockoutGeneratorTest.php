<?php

declare(strict_types=1);

use App\Domain\Tournaments\Generators\KnockoutGenerator;

it('generates a single elimination bracket for 4 teams', function (): void {
    $generator = new KnockoutGenerator;

    $matches = $generator->generate([1, 2, 3, 4]);

    expect($matches)->toHaveCount(3)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(2);
});

it('generates a single elimination bracket for 6 teams with byes', function (): void {
    $generator = new KnockoutGenerator;

    $matches = $generator->generate([1, 2, 3, 4, 5, 6]);

    expect($matches)->toHaveCount(7)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(3);
});

it('generates a single elimination bracket for 8 teams', function (): void {
    $generator = new KnockoutGenerator;

    $matches = $generator->generate([1, 2, 3, 4, 5, 6, 7, 8]);

    expect($matches)->toHaveCount(7)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(3);
});

it('handles byes in the first round for non power of two team counts', function (): void {
    $generator = new KnockoutGenerator;

    $matches = $generator->generate([1, 2, 3, 4, 5, 6]);

    $firstRound = array_values(array_filter($matches, fn ($match) => $match->round === 1));
    $byeMatches = array_values(array_filter(
        $firstRound,
        fn ($match) => $match->homeTeamId === 'BYE' || $match->awayTeamId === 'BYE',
    ));

    expect($byeMatches)->toHaveCount(2);
});

it('generates correct round counts by bracket size', function (array $teams, int $expectedRounds): void {
    $generator = new KnockoutGenerator;

    $matches = $generator->generate($teams);
    $rounds = array_unique(array_map(fn ($match) => $match->round, $matches));

    expect($rounds)->toHaveCount($expectedRounds);
})->with([
    [[1, 2, 3, 4], 2],
    [[1, 2, 3, 4, 5, 6], 3],
    [[1, 2, 3, 4, 5, 6, 7, 8], 3],
]);
