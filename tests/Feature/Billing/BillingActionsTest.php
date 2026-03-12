<?php

declare(strict_types=1);

use App\Domain\Billing\Actions\CreateBillingPortalSession;
use App\Domain\Billing\Actions\CreateCheckoutSession;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Services\StripeApi;
use App\Models\Organization;

it('creates stripe customer locally and starts checkout session with configured payment methods', function (): void {
    config()->set('billing.plans.starter.stripe_price_id', 'price_starter_123');
    config()->set('billing.stripe.checkout.payment_method_types', ['card', 'ideal', 'bancontact', 'sepa_debit']);

    $organization = Organization::factory()->create([
        'locale' => 'nl',
    ]);

    $stripeApi = Mockery::mock(StripeApi::class);

    $stripeApi
        ->shouldReceive('createCustomer')
        ->once()
        ->withArgs(function (array $payload) use ($organization): bool {
            return $payload['name'] === $organization->name
                && ($payload['metadata']['organization_id'] ?? null) === (string) $organization->id;
        })
        ->andReturn((object) ['id' => 'cus_test_123']);

    $stripeApi
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->withArgs(function (array $payload): bool {
            return $payload['mode'] === 'subscription'
                && $payload['customer'] === 'cus_test_123'
                && $payload['line_items'][0]['price'] === 'price_starter_123'
                && $payload['payment_method_types'] === ['card', 'ideal', 'bancontact', 'sepa_debit'];
        })
        ->andReturn((object) [
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.test/session/cs_test_123',
        ]);

    app()->instance(StripeApi::class, $stripeApi);

    $session = app(CreateCheckoutSession::class)(
        organization: $organization,
        plan: BillingPlan::Starter,
        successUrl: 'https://toernooigenerator.test/settings/billing?checkout=success',
        cancelUrl: 'https://toernooigenerator.test/settings/billing?checkout=cancel',
    );

    expect($session->id)->toBe('cs_test_123')
        ->and($session->url)->toBe('https://checkout.stripe.test/session/cs_test_123')
        ->and($organization->fresh()->stripe_customer_id)->toBe('cus_test_123');
});

it('creates billing portal session url for organization admins', function (): void {
    $organization = Organization::factory()->create();

    $stripeApi = Mockery::mock(StripeApi::class);

    $stripeApi
        ->shouldReceive('createCustomer')
        ->once()
        ->andReturn((object) ['id' => 'cus_portal_123']);

    $stripeApi
        ->shouldReceive('createBillingPortalSession')
        ->once()
        ->withArgs(function (array $payload): bool {
            return $payload['customer'] === 'cus_portal_123'
                && str_contains($payload['return_url'], '/settings/billing');
        })
        ->andReturn((object) ['url' => 'https://billing.stripe.test/session/portal_123']);

    app()->instance(StripeApi::class, $stripeApi);

    $url = app(CreateBillingPortalSession::class)($organization, 'https://toernooigenerator.test/settings/billing');

    expect($url)->toBe('https://billing.stripe.test/session/portal_123')
        ->and($organization->fresh()->stripe_customer_id)->toBe('cus_portal_123');
});
