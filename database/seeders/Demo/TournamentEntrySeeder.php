<?php

namespace Database\Seeders\Demo;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use Illuminate\Database\Seeder;

class TournamentEntrySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::tournaments() as $sportSlug => $tournamentData) {
            $tournament = Tournament::query()
                ->where('organization_id', $organization->id)
                ->where('name', $tournamentData['name'])
                ->firstOrFail();

            $sport = Sport::query()
                ->where('organization_id', $organization->id)
                ->where('slug', $sportSlug)
                ->firstOrFail();

            $teams = Team::query()
                ->where('organization_id', $organization->id)
                ->where('sport_id', $sport->id)
                ->orderBy('id')
                ->limit($tournamentData['entry_count'])
                ->get();

            foreach ($teams as $index => $team) {
                TournamentEntry::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'tournament_id' => $tournament->id,
                        'team_id' => $team->id,
                    ],
                    [
                        'player_id' => null,
                        'seed' => $index + 1,
                    ],
                );
            }
        }
    }
}
