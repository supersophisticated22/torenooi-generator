<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'sport_id' => Sport::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->sentence(3),
            'type' => TournamentType::HalfCompetition,
            'final_type' => TournamentFinalType::FinalOnly,
            'pool_count' => 2,
            'match_duration_minutes' => 20,
            'break_duration_minutes' => 5,
            'final_break_minutes' => 10,
            'scheduled_start_at' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'scheduled_end_at' => fake()->dateTimeBetween('+1 week', '+2 weeks'),
            'status' => TournamentStatus::Draft,
        ];
    }
}
