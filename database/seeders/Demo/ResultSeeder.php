<?php

namespace Database\Seeders\Demo;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\MatchResult;
use App\Models\Organization;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class ResultSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::tournaments() as $tournamentData) {
            $tournament = Tournament::query()
                ->where('organization_id', $organization->id)
                ->where('name', $tournamentData['name'])
                ->firstOrFail();

            $matches = $tournament->matches()
                ->orderBy('round')
                ->orderBy('starts_at')
                ->get();

            $completedCount = max(1, (int) floor($matches->count() * 0.6));

            foreach ($matches as $index => $match) {
                if ($match->home_team_id === null || $match->away_team_id === null) {
                    continue;
                }

                if ($index >= $completedCount) {
                    MatchResult::query()->where('match_id', $match->id)->delete();
                    $match->update(['status' => MatchStatus::Scheduled]);

                    continue;
                }

                $homeScore = random_int(0, 5);
                $awayScore = random_int(0, 5);

                if ($homeScore === $awayScore) {
                    $awayScore++;
                }

                $winnerTeamId = $homeScore > $awayScore
                    ? $match->home_team_id
                    : $match->away_team_id;

                MatchResult::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'match_id' => $match->id,
                    ],
                    [
                        'home_score' => $homeScore,
                        'away_score' => $awayScore,
                        'winner_team_id' => $winnerTeamId,
                        'notes' => 'Generated demo result',
                    ],
                );

                $match->update(['status' => MatchStatus::Completed]);
            }
        }
    }
}
