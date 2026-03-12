<?php

namespace Database\Seeders\Demo;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::categories() as $sportSlug => $categoryData) {
            $sport = Sport::query()
                ->where('organization_id', $organization->id)
                ->where('slug', $sportSlug)
                ->firstOrFail();

            Category::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $categoryData['slug'],
                ],
                [
                    'sport_id' => $sport->id,
                    'name' => $categoryData['name'],
                ],
            );
        }
    }
}
