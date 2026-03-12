<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\BillingFeature;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Plans\PlanCatalog;
use App\Models\Organization;

class SubscriptionLimits
{
    public function __construct(private PlanCatalog $planCatalog) {}

    public function tournamentsLimit(Organization $organization): ?int
    {
        return $this->planCatalog->limit($organization->activePlan(), 'tournaments');
    }

    public function teamsLimit(Organization $organization): ?int
    {
        return $this->planCatalog->limit($organization->activePlan(), 'teams');
    }

    public function hasFeature(Organization $organization, BillingFeature $feature): bool
    {
        return $this->planCatalog->hasFeature($organization->activePlan(), $feature);
    }

    public function hasPaidSubscription(Organization $organization): bool
    {
        if (! in_array($organization->subscription_status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)) {
            return false;
        }

        return in_array($organization->subscription_plan, [BillingPlan::Starter, BillingPlan::Pro, BillingPlan::Enterprise], true);
    }
}
