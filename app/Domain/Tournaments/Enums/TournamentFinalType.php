<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Enums;

enum TournamentFinalType: string
{
    case None = 'none';
    case FinalOnly = 'final_only';
    case FinalAndThirdPlace = 'final_and_third_place';
}
