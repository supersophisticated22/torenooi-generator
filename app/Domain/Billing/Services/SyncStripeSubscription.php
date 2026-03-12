<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Plans\PlanCatalog;
use App\Models\Organization;
use App\Models\Subscription;
use Carbon\CarbonImmutable;

class SyncStripeSubscription
{
    public function __construct(private PlanCatalog $planCatalog) {}

    public function sync(Organization $organization, object $subscription): Subscription
    {
        $item = $subscription->items->data[0] ?? null;
        $priceId = $item?->price?->id;
        $plan = $this->planCatalog->planForPriceId(is_string($priceId) ? $priceId : null);
        $status = SubscriptionStatus::tryFrom((string) $subscription->status) ?? SubscriptionStatus::Incomplete;

        $record = Subscription::query()->updateOrCreate(
            ['stripe_subscription_id' => (string) $subscription->id],
            [
                'organization_id' => $organization->id,
                'stripe_price_id' => is_string($priceId) ? $priceId : null,
                'plan_code' => $plan,
                'status' => $status,
                'quantity' => isset($item?->quantity) ? (int) $item->quantity : null,
                'current_period_start' => $this->fromStripeTimestamp($subscription->current_period_start ?? null),
                'current_period_end' => $this->fromStripeTimestamp($subscription->current_period_end ?? null),
                'cancel_at' => $this->fromStripeTimestamp($subscription->cancel_at ?? null),
                'canceled_at' => $this->fromStripeTimestamp($subscription->canceled_at ?? null),
                'trial_ends_at' => $this->fromStripeTimestamp($subscription->trial_end ?? null),
                'metadata' => is_object($subscription->metadata) ? $subscription->metadata->toArray() : null,
            ],
        );

        $organization->forceFill([
            'subscription_plan' => $plan,
            'subscription_status' => $status,
            'trial_ends_at' => $this->fromStripeTimestamp($subscription->trial_end ?? null),
            'subscription_ends_at' => $this->fromStripeTimestamp($subscription->ended_at ?? null),
        ])->save();

        return $record;
    }

    private function fromStripeTimestamp(mixed $timestamp): ?CarbonImmutable
    {
        if (! is_int($timestamp)) {
            return null;
        }

        return CarbonImmutable::createFromTimestampUTC($timestamp);
    }
}
