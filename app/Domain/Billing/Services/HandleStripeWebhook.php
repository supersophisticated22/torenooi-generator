<?php

namespace App\Domain\Billing\Services;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Actions\SyncStripeSubscriptionAction;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\BillingEvent;
use App\Models\Organization;
use App\Services\OnboardingFlow;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class HandleStripeWebhook
{
    private const SUPPORTED_EVENTS = [
        'checkout.session.completed',
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'invoice.paid',
        'invoice.payment_failed',
    ];

    public function __construct(
        private StripeApi $stripeApi,
        private SyncStripeSubscriptionAction $syncStripeSubscription,
        private OnboardingFlow $onboardingFlow,
    ) {}

    public function handle(string $payload, string $signature): bool
    {
        $event = $this->stripeApi->verifyWebhook($payload, $signature);
        $processed = false;

        DB::transaction(function () use (&$processed, $event): void {
            $eventObject = $event->data->object;
            $organization = $this->resolveOrganization($event->type, $eventObject);

            $billingEvent = BillingEvent::query()->firstOrCreate([
                'event_id' => (string) $event->id,
            ], [
                'provider' => 'stripe',
                'event_type' => (string) $event->type,
                'organization_id' => $organization?->id,
                'status' => 'processing',
                'payload' => Arr::only($event->toArray(), ['id', 'type', 'created', 'data']),
            ]);

            if (! $billingEvent->wasRecentlyCreated) {
                return;
            }

            if (in_array($event->type, self::SUPPORTED_EVENTS, true)) {
                $this->processEvent($event->type, $eventObject, $organization);
            }

            $billingEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            $processed = true;
        });

        return $processed;
    }

    private function processEvent(string $eventType, object $eventObject, ?Organization $organization): void
    {
        if ($organization === null) {
            return;
        }

        if ($eventType === 'checkout.session.completed') {
            $subscriptionId = $eventObject->subscription ?? null;

            if (is_string($subscriptionId) && $subscriptionId !== '') {
                ($this->syncStripeSubscription)($organization, $subscriptionId);
            }

            return;
        }

        if (str_starts_with($eventType, 'customer.subscription.')) {
            $subscriptionId = $eventObject->id ?? null;

            if (is_string($subscriptionId) && $subscriptionId !== '') {
                ($this->syncStripeSubscription)($organization, $subscriptionId);
            }

            return;
        }

        if (str_starts_with($eventType, 'invoice.')) {
            $subscriptionId = $eventObject->subscription ?? null;

            if (is_string($subscriptionId) && $subscriptionId !== '') {
                ($this->syncStripeSubscription)($organization, $subscriptionId);
            }
        }

        if ($eventType === 'invoice.payment_failed') {
            $organization->forceFill(['subscription_status' => SubscriptionStatus::PastDue])->save();
        }

        if ($this->onboardingFlow->isOrganizationSubscribed($organization)) {
            $organization->users()
                ->wherePivot('role', OrganizationRole::OrganizationAdmin->value)
                ->get()
                ->each(fn ($user) => $this->onboardingFlow->markSubscribed($user));
        }
    }

    private function resolveOrganization(string $eventType, object $eventObject): ?Organization
    {
        $customerId = null;

        if (str_starts_with($eventType, 'customer.subscription.')) {
            $customerId = $eventObject->customer ?? null;
        }

        if ($eventType === 'checkout.session.completed' || str_starts_with($eventType, 'invoice.')) {
            $customerId = $eventObject->customer ?? null;
        }

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        return Organization::query()->where('stripe_customer_id', $customerId)->first();
    }
}
