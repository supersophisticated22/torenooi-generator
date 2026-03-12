<?php

namespace App\Domain\Billing\DTOs;

readonly class CheckoutSessionData
{
    public function __construct(
        public string $id,
        public string $url,
    ) {}
}
