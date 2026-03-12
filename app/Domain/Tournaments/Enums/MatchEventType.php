<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Enums;

enum MatchEventType: string
{
    case Goal = 'goal';
    case YellowCard = 'yellow_card';
    case RedCard = 'red_card';
    case GreenCard = 'green_card';
    case Note = 'note';
}
