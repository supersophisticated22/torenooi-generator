<?php

namespace Database\Seeders\Demo;

use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::tournaments() as $sportSlug => $tournamentData) {
            $sport = Sport::query()
                ->where('organization_id', $organization->id)
                ->where('slug', $sportSlug)
                ->firstOrFail();

            $category = Category::query()
                ->where('organization_id', $organization->id)
                ->where('slug', DemoCatalog::categories()[$sportSlug]['slug'])
                ->firstOrFail();

            $event = Event::query()
                ->where('organization_id', $organization->id)
                ->where('name', DemoCatalog::events()[$sportSlug]['name'])
                ->firstOrFail();

            Tournament::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => $tournamentData['name'],
                ],
                [
                    'event_id' => $event->id,
                    'sport_id' => $sport->id,
                    'category_id' => $category->id,
                    'type' => TournamentType::HalfCompetition,
                    'final_type' => TournamentFinalType::None,
                    'pool_count' => $tournamentData['pool_count'],
                    'match_duration_minutes' => $tournamentData['match_duration_minutes'],
                    'break_duration_minutes' => $tournamentData['break_duration_minutes'],
                    'final_break_minutes' => $tournamentData['final_break_minutes'],
                    'scheduled_start_at' => $tournamentData['scheduled_start_at'],
                    'scheduled_end_at' => DemoCatalog::events()[$sportSlug]['ends_at'],
                    'status' => TournamentStatus::Scheduled,
                ],
            );
        }
    }
}
