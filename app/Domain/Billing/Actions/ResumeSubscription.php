<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Services\StripeApi;
use App\Domain\Billing\Services\SyncStripeSubscription;
use App\Models\Organization;
use App\Models\Subscription;

class ResumeSubscription
{
    public function __construct(
        private StripeApi $stripeApi,
        private SyncStripeSubscription $syncStripeSubscription,
    ) {}

    public function __invoke(Organization $organization, string $stripeSubscriptionId): void
    {
        $ownedSubscriptionExists = Subscription::query()
            ->where('organization_id', $organization->id)
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->exists();

        if (! $ownedSubscriptionExists) {
            throw new BillingException('Subscription does not belong to the current organization.');
        }

        $subscription = $this->stripeApi->resumeSubscription($stripeSubscriptionId, [
            'billing_cycle_anchor' => 'unchanged',
        ]);

        $this->syncStripeSubscription->sync($organization, $subscription);
    }
}
