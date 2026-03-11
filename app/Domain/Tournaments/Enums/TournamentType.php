<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Enums;

enum TournamentType: string
{
    case HalfCompetition = 'half_competition';
    case FullCompetition = 'full_competition';
    case Knockout = 'knockout';
    case Playoff = 'playoff';
    case Ranking = 'ranking';
}
