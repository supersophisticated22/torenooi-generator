<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Senior',
            'Junior',
            'Under 18',
            'Under 16',
        ]);

        return [
            'organization_id' => Organization::factory(),
            'sport_id' => Sport::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->numberBetween(10, 9999),
        ];
    }
}
