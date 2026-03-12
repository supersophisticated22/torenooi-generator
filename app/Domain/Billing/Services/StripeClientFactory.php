<?php

namespace App\Domain\Billing\Services;

use Stripe\StripeClient;

class StripeClientFactory
{
    public function make(): StripeClient
    {
        $secret = (string) config('services.stripe.secret');

        return new StripeClient($secret);
    }
}
