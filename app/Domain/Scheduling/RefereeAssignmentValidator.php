<?php

namespace App\Domain\Scheduling;

use App\Models\Referee;
use App\Models\TournamentMatch;

class RefereeAssignmentValidator
{
    /**
     * @return array<int, string>
     */
    public function validateForMatch(TournamentMatch $match, Referee $referee): array
    {
        $errors = [];

        if ($match->referees()->whereKey($referee->id)->exists()) {
            $errors[] = 'Referee is already assigned to this match.';
        }

        if ($referee->sport_id !== null && (int) $referee->sport_id !== (int) $match->tournament->sport_id) {
            $errors[] = 'Referee sport does not match the tournament sport.';
        }

        return array_merge($errors, $this->conflictViolations($match, $referee));
    }

    /**
     * @return array<int, string>
     */
    private function conflictViolations(TournamentMatch $match, Referee $referee): array
    {
        return [];
    }
}
