<?php

namespace Database\Seeders\Demo;

use App\Models\Organization;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        $teams = Team::query()
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->get();

        foreach ($teams as $team) {
            for ($index = 1; $index <= 12; $index++) {
                $email = sprintf(
                    '%s-player-%02d@demo.test',
                    str($team->name)->slug()->value(),
                    $index,
                );

                $player = Player::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'email' => $email,
                    ],
                    [
                        'team_id' => $team->id,
                        'first_name' => 'Player',
                        'last_name' => sprintf('%s %02d', $team->short_name ?? 'T', $index),
                    ],
                );

                $team->players()->syncWithoutDetaching([
                    $player->id => [
                        'organization_id' => $organization->id,
                        'jersey_number' => $index,
                    ],
                ]);
            }
        }
    }
}
