<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'venue_id' => Venue::factory(),
            'sport_id' => Sport::factory(),
            'name' => 'Field '.fake()->numberBetween(1, 12),
            'code' => fake()->bothify('F-##'),
        ];
    }
}
