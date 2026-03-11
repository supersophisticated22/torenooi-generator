<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportRule>
 */
class SportRuleFactory extends Factory
{
    protected $model = SportRule::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'sport_id' => Sport::factory(),
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
        ];
    }
}
