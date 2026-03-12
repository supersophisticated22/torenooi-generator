<?php

namespace App\Domain\Billing\Enums;

enum BillingPlan: string
{
    case Free = 'free';
    case Starter = 'starter';
    case Pro = 'pro';
    case Enterprise = 'enterprise';
}
