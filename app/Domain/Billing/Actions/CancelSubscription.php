<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Services\StripeApi;
use App\Domain\Billing\Services\SyncStripeSubscription;
use App\Models\Organization;
use App\Models\Subscription;

class CancelSubscription
{
    public function __construct(
        private StripeApi $stripeApi,
        private SyncStripeSubscription $syncStripeSubscription,
    ) {}

    public function __invoke(Organization $organization, string $stripeSubscriptionId, bool $atPeriodEnd = true): void
    {
        $ownedSubscriptionExists = Subscription::query()
            ->where('organization_id', $organization->id)
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->exists();

        if (! $ownedSubscriptionExists) {
            throw new BillingException('Subscription does not belong to the current organization.');
        }

        $subscription = $atPeriodEnd
            ? $this->stripeApi->updateSubscription($stripeSubscriptionId, ['cancel_at_period_end' => true])
            : $this->stripeApi->cancelSubscription($stripeSubscriptionId);

        $this->syncStripeSubscription->sync($organization, $subscription);
    }
}
