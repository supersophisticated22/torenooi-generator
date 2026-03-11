<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\Field;
use App\Models\Organization;
use App\Models\Pool;
use App\Models\Referee;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentMatch>
 */
class TournamentMatchFactory extends Factory
{
    protected $model = TournamentMatch::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $end = (clone $start)->modify('+1 hour');

        return [
            'organization_id' => Organization::factory(),
            'tournament_id' => Tournament::factory(),
            'pool_id' => Pool::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'field_id' => Field::factory(),
            'referee_id' => Referee::factory(),
            'starts_at' => $start,
            'ends_at' => $end,
            'round' => fake()->numberBetween(1, 10),
            'status' => MatchStatus::Scheduled,
        ];
    }
}
