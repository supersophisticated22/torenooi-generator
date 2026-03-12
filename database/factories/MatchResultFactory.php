<?php

namespace Database\Factories;

use App\Models\MatchResult;
use App\Models\Organization;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchResult>
 */
class MatchResultFactory extends Factory
{
    protected $model = MatchResult::class;

    public function definition(): array
    {
        $homeScore = fake()->numberBetween(0, 5);
        $awayScore = fake()->numberBetween(0, 5);

        if ($homeScore === $awayScore) {
            $awayScore++;
        }

        return [
            'organization_id' => Organization::factory(),
            'match_id' => TournamentMatch::factory(),
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'winner_team_id' => null,
            'notes' => fake()->sentence(),
        ];
    }
}
