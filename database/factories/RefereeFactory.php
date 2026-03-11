<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Referee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referee>
 */
class RefereeFactory extends Factory
{
    protected $model = Referee::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
