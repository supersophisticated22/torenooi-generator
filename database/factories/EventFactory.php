<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $end = (clone $start)->modify('+1 day');

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->sentence(3),
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => EventStatus::Draft,
        ];
    }
}
