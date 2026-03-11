<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Pool;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pool>
 */
class PoolFactory extends Factory
{
    protected $model = Pool::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'tournament_id' => Tournament::factory(),
            'name' => 'Pool '.fake()->randomLetter(),
            'sequence' => fake()->numberBetween(1, 16),
        ];
    }
}
