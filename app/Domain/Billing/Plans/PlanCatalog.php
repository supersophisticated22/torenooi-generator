<?php

namespace App\Domain\Billing\Plans;

use App\Domain\Billing\Enums\BillingFeature;
use App\Domain\Billing\Enums\BillingPlan;

class PlanCatalog
{
    public function plans(): array
    {
        return config('billing.plans', []);
    }

    public function plan(BillingPlan $plan): array
    {
        return $this->plans()[$plan->value] ?? [];
    }

    public function priceId(BillingPlan $plan): ?string
    {
        $priceId = $this->plan($plan)['stripe_price_id'] ?? null;

        return is_string($priceId) && $priceId !== '' ? $priceId : null;
    }

    public function planForPriceId(?string $priceId): ?BillingPlan
    {
        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        foreach ($this->plans() as $code => $metadata) {
            if (($metadata['stripe_price_id'] ?? null) === $priceId) {
                return BillingPlan::tryFrom((string) $code);
            }
        }

        return null;
    }

    public function limit(BillingPlan $plan, string $limit): ?int
    {
        $value = $this->plan($plan)['limits'][$limit] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function hasFeature(BillingPlan $plan, BillingFeature $feature): bool
    {
        $features = $this->plan($plan)['features'] ?? [];

        return in_array($feature->value, $features, true);
    }
}
