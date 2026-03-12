<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\DTOs\CheckoutSessionData;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Plans\PlanCatalog;
use App\Domain\Billing\Services\StripeApi;
use App\Models\Organization;

class CreateCheckoutSession
{
    public function __construct(
        private CreateStripeCustomer $createStripeCustomer,
        private StripeApi $stripeApi,
        private PlanCatalog $planCatalog,
    ) {}

    public function __invoke(Organization $organization, BillingPlan $plan, string $successUrl, string $cancelUrl): CheckoutSessionData
    {
        $priceId = $this->planCatalog->priceId($plan);

        if ($priceId === null) {
            throw new BillingException('Missing Stripe price id for selected plan.');
        }

        $customerId = ($this->createStripeCustomer)($organization);

        $session = $this->stripeApi->createCheckoutSession([
            'mode' => 'subscription',
            'customer' => $customerId,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'payment_method_types' => config('billing.stripe.checkout.payment_method_types', ['card']),
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'auto',
            'locale' => $organization->locale,
            'subscription_data' => [
                'metadata' => [
                    'organization_id' => (string) $organization->id,
                    'plan_code' => $plan->value,
                ],
            ],
        ]);

        return new CheckoutSessionData(
            id: (string) $session->id,
            url: (string) $session->url,
        );
    }
}
