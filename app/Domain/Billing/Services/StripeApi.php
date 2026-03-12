<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Exceptions\BillingException;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeApi
{
    public function __construct(private StripeClientFactory $factory) {}

    public function createCustomer(array $payload): object
    {
        return $this->client()->customers->create($payload);
    }

    public function createCheckoutSession(array $payload): object
    {
        return $this->client()->checkout->sessions->create($payload);
    }

    public function updateSubscription(string $subscriptionId, array $payload): object
    {
        return $this->client()->subscriptions->update($subscriptionId, $payload);
    }

    public function cancelSubscription(string $subscriptionId, array $payload = []): object
    {
        return $this->client()->subscriptions->cancel($subscriptionId, $payload);
    }

    public function resumeSubscription(string $subscriptionId, array $payload = []): object
    {
        return $this->client()->subscriptions->resume($subscriptionId, $payload);
    }

    public function retrieveSubscription(string $subscriptionId): object
    {
        return $this->client()->subscriptions->retrieve($subscriptionId, []);
    }

    public function createBillingPortalSession(array $payload): object
    {
        return $this->client()->billingPortal->sessions->create($payload);
    }

    public function verifyWebhook(string $payload, string $signature): Event
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            throw new BillingException('Stripe webhook secret is not configured.');
        }

        try {
            return Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            throw new BillingException('Stripe webhook signature verification failed.', 0, $exception);
        }
    }

    private function client(): StripeClient
    {
        return $this->factory->make();
    }
}
