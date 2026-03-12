<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Services\StripeApi;
use App\Models\Organization;

class CreateStripeCustomer
{
    public function __construct(private StripeApi $stripeApi) {}

    public function __invoke(Organization $organization): string
    {
        if (is_string($organization->stripe_customer_id) && $organization->stripe_customer_id !== '') {
            return $organization->stripe_customer_id;
        }

        $customer = $this->stripeApi->createCustomer([
            'name' => $organization->name,
            'email' => $organization->billing_email,
            'metadata' => [
                'organization_id' => (string) $organization->id,
                'organization_slug' => $organization->slug,
            ],
        ]);

        $organization->forceFill([
            'stripe_customer_id' => (string) $customer->id,
        ])->save();

        return (string) $customer->id;
    }
}
