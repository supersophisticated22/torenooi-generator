<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DemoSeeder::class,
        ]);
    }
}
