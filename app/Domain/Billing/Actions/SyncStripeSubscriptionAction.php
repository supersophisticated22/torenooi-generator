<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Services\StripeApi;
use App\Domain\Billing\Services\SyncStripeSubscription;
use App\Models\Organization;

class SyncStripeSubscriptionAction
{
    public function __construct(
        private StripeApi $stripeApi,
        private SyncStripeSubscription $syncStripeSubscription,
    ) {}

    public function __invoke(Organization $organization, string $stripeSubscriptionId): void
    {
        $subscription = $this->stripeApi->retrieveSubscription($stripeSubscriptionId);

        $this->syncStripeSubscription->sync($organization, $subscription);
    }
}
