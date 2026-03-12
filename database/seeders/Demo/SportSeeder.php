<?php

namespace Database\Seeders\Demo;

use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::sports() as $sportData) {
            Sport::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $sportData['slug'],
                ],
                ['name' => $sportData['name']],
            );
        }
    }
}
