<?php

declare(strict_types=1);

namespace App\Domain\Tournaments\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
