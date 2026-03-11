<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sport>
 */
class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Football',
            'Basketball',
            'Tennis',
            'Swimming',
            'Volleyball',
        ]);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->numberBetween(100, 9999),
        ];
    }
}
