<?php

namespace Database\Seeders\Demo;

use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        Venue::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Amsterdam Sports Complex',
            ],
            ['address' => 'Olympisch Stadion 2, Amsterdam'],
        );
    }
}
