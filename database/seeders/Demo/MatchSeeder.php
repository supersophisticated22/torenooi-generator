<?php

namespace Database\Seeders\Demo;

use App\Domain\Tournaments\Services\GenerateTournamentMatches;
use App\Models\Organization;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        $generator = app(GenerateTournamentMatches::class);

        foreach (DemoCatalog::tournaments() as $tournamentData) {
            $tournament = Tournament::query()
                ->where('organization_id', $organization->id)
                ->where('name', $tournamentData['name'])
                ->firstOrFail();

            $generator->handle($tournament, force: true);
        }
    }
}
