<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentEntry>
 */
class TournamentEntryFactory extends Factory
{
    protected $model = TournamentEntry::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'tournament_id' => Tournament::factory(),
            'team_id' => Team::factory(),
            'player_id' => null,
            'seed' => fake()->numberBetween(1, 64),
        ];
    }
}
