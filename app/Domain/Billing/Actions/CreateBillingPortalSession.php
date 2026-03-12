<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Services\StripeApi;
use App\Models\Organization;

class CreateBillingPortalSession
{
    public function __construct(
        private CreateStripeCustomer $createStripeCustomer,
        private StripeApi $stripeApi,
    ) {}

    public function __invoke(Organization $organization, string $returnUrl): string
    {
        $customerId = ($this->createStripeCustomer)($organization);

        $session = $this->stripeApi->createBillingPortalSession([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);

        $url = (string) ($session->url ?? '');

        if ($url === '') {
            throw new BillingException('Stripe billing portal URL is missing.');
        }

        return $url;
    }
}
