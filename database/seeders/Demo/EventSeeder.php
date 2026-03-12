<?php

namespace Database\Seeders\Demo;

use App\Domain\Tournaments\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::events() as $eventData) {
            Event::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => $eventData['name'],
                ],
                [
                    'starts_at' => $eventData['starts_at'],
                    'ends_at' => $eventData['ends_at'],
                    'status' => EventStatus::Published,
                ],
            );
        }
    }
}
