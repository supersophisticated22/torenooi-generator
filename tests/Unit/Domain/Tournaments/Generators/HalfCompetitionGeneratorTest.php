<?php

declare(strict_types=1);

use App\Domain\Tournaments\Generators\HalfCompetitionGenerator;

it('generates a round robin schedule for 4 teams', function (): void {
    $generator = new HalfCompetitionGenerator;

    $matches = $generator->generate([1, 2, 3, 4]);

    expect($matches)->toHaveCount(6)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(3);
});

it('generates a round robin schedule for 5 teams with byes', function (): void {
    $generator = new HalfCompetitionGenerator;

    $matches = $generator->generate([1, 2, 3, 4, 5]);

    expect($matches)->toHaveCount(10)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(5);
});

it('prevents duplicate teams', function (): void {
    $generator = new HalfCompetitionGenerator;

    expect(fn () => $generator->generate([1, 1, 2, 3]))
        ->toThrow(InvalidArgumentException::class);
});

it('generates the correct number of matches', function (array $teams, int $expectedMatches): void {
    $generator = new HalfCompetitionGenerator;

    $matches = $generator->generate($teams);

    expect($matches)->toHaveCount($expectedMatches);
})->with([
    [[1, 2, 3, 4], 6],
    [[1, 2, 3, 4, 5], 10],
]);

it('never creates self matches', function (): void {
    $generator = new HalfCompetitionGenerator;

    $matches = $generator->generate([1, 2, 3, 4, 5]);

    foreach ($matches as $match) {
        expect($match->homeTeamId)->not->toBe($match->awayTeamId);
    }
});
