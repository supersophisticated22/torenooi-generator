<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            UserSeeder::class,
            SportSeeder::class,
            CategorySeeder::class,
            TeamSeeder::class,
            PlayerSeeder::class,
            VenueSeeder::class,
            FieldSeeder::class,
            EventSeeder::class,
            TournamentSeeder::class,
            TournamentEntrySeeder::class,
            MatchSeeder::class,
            ResultSeeder::class,
        ]);
    }
}
