<?php

namespace Database\Seeders\Demo;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->updateOrCreate(
            ['slug' => DemoCatalog::ORGANIZATION_SLUG],
            ['name' => DemoCatalog::ORGANIZATION_NAME],
        );
    }
}
