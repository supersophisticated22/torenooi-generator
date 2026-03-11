<?php

declare(strict_types=1);

use App\Domain\Tournaments\Enums\TournamentType;
use App\Domain\Tournaments\Generators\FullCompetitionGenerator;
use App\Domain\Tournaments\Generators\HalfCompetitionGenerator;
use App\Domain\Tournaments\Generators\KnockoutGenerator;
use App\Domain\Tournaments\Generators\PlayoffGenerator;
use App\Domain\Tournaments\Generators\RankingGenerator;
use App\Domain\Tournaments\TournamentEngine;

it('resolves the correct generator for each tournament type', function (TournamentType $type, string $generatorClass): void {
    $engine = new TournamentEngine;

    expect($engine->resolveGenerator($type))->toBeInstanceOf($generatorClass);
})->with([
    [TournamentType::HalfCompetition, HalfCompetitionGenerator::class],
    [TournamentType::FullCompetition, FullCompetitionGenerator::class],
    [TournamentType::Knockout, KnockoutGenerator::class],
    [TournamentType::Playoff, PlayoffGenerator::class],
    [TournamentType::Ranking, RankingGenerator::class],
]);

it('delegates generation to the resolved generator', function (): void {
    $engine = new TournamentEngine;

    $matches = $engine->generate(TournamentType::HalfCompetition, [1, 2, 3, 4]);

    expect($matches)->toHaveCount(6);
});

it('delegates knockout generation to knockout generator', function (): void {
    $engine = new TournamentEngine;

    $matches = $engine->generate(TournamentType::Knockout, [1, 2, 3, 4]);

    expect($matches)->toHaveCount(3);
});
