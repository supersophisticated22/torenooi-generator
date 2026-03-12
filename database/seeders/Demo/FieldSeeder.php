<?php

namespace Database\Seeders\Demo;

use App\Models\Field;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        $venue = Venue::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Amsterdam Sports Complex')
            ->firstOrFail();

        $football = Sport::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'football')
            ->firstOrFail();

        $basketball = Sport::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'basketball')
            ->firstOrFail();

        Field::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'code' => 'F-A1',
            ],
            [
                'venue_id' => $venue->id,
                'sport_id' => $football->id,
                'name' => 'Main Football Pitch',
            ],
        );

        Field::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'code' => 'B-C1',
            ],
            [
                'venue_id' => $venue->id,
                'sport_id' => $basketball->id,
                'name' => 'Main Basketball Court',
            ],
        );
    }
}
