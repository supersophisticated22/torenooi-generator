<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Enums;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
