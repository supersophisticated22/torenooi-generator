<?php

namespace Database\Seeders\Demo;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::teamNames() as $sportSlug => $teamNames) {
            $sport = Sport::query()
                ->where('organization_id', $organization->id)
                ->where('slug', $sportSlug)
                ->firstOrFail();

            $category = Category::query()
                ->where('organization_id', $organization->id)
                ->where('slug', DemoCatalog::categories()[$sportSlug]['slug'])
                ->firstOrFail();

            foreach ($teamNames as $teamName) {
                Team::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => $teamName,
                    ],
                    [
                        'sport_id' => $sport->id,
                        'category_id' => $category->id,
                        'short_name' => Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $teamName), 0, 3)),
                    ],
                );
            }
        }
    }
}
