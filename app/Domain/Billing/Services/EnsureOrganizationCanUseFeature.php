<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\BillingFeature;
use App\Domain\Billing\Exceptions\FeatureLimitExceededException;
use App\Models\Organization;

class EnsureOrganizationCanUseFeature
{
    public function __construct(private SubscriptionLimits $subscriptionLimits) {}

    public function forTournamentCreation(Organization $organization): void
    {
        $limit = $this->subscriptionLimits->tournamentsLimit($organization);

        if ($limit !== null && $organization->tournaments()->count() >= $limit) {
            throw new FeatureLimitExceededException('You reached the tournament limit for your current plan. Upgrade to continue.');
        }
    }

    public function forTeamCreation(Organization $organization): void
    {
        $limit = $this->subscriptionLimits->teamsLimit($organization);

        if ($limit !== null && $organization->teams()->count() >= $limit) {
            throw new FeatureLimitExceededException('You reached the team limit for your current plan. Upgrade to continue.');
        }
    }

    public function forFeature(Organization $organization, BillingFeature $feature): void
    {
        if (! $this->subscriptionLimits->hasFeature($organization, $feature)) {
            throw new FeatureLimitExceededException('This feature is not available on your current plan.');
        }
    }
}
