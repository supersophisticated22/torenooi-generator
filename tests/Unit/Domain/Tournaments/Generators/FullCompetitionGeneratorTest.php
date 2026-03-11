<?php

declare(strict_types=1);

use App\Domain\Tournaments\Generators\FullCompetitionGenerator;

it('generates a full competition schedule for 4 teams', function (): void {
    $generator = new FullCompetitionGenerator;

    $matches = $generator->generate([1, 2, 3, 4]);

    expect($matches)->toHaveCount(12)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(6);
});

it('generates a full competition schedule for 5 teams', function (): void {
    $generator = new FullCompetitionGenerator;

    $matches = $generator->generate([1, 2, 3, 4, 5]);

    expect($matches)->toHaveCount(20)
        ->and(array_unique(array_map(fn ($match) => $match->round, $matches)))->toHaveCount(10);
});

it('generates expected match counts', function (array $teams, int $expectedMatches): void {
    $generator = new FullCompetitionGenerator;

    $matches = $generator->generate($teams);

    expect($matches)->toHaveCount($expectedMatches);
})->with([
    [[1, 2, 3, 4], 12],
    [[1, 2, 3, 4, 5], 20],
]);

it('creates mirrored home and away fixtures in second leg', function (): void {
    $generator = new FullCompetitionGenerator;

    $matches = $generator->generate([1, 2, 3, 4]);
    $firstLeg = array_slice($matches, 0, 6);
    $secondLeg = array_slice($matches, 6);

    $firstLegPairs = array_map(
        fn ($match) => $match->homeTeamId.'-'.$match->awayTeamId,
        $firstLeg,
    );

    $mirroredSecondLegPairs = array_map(
        fn ($match) => $match->awayTeamId.'-'.$match->homeTeamId,
        $secondLeg,
    );

    sort($firstLegPairs);
    sort($mirroredSecondLegPairs);

    expect($mirroredSecondLegPairs)->toBe($firstLegPairs);
});
