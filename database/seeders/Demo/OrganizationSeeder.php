<?php

namespace Database\Seeders\Demo;

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->updateOrCreate(
            ['slug' => DemoCatalog::ORGANIZATION_SLUG],
            [
                'name' => DemoCatalog::ORGANIZATION_NAME,
                'country' => 'NL',
                'locale' => 'nl',
                'timezone' => 'Europe/Amsterdam',
                'selected_plan' => BillingPlan::Pro,
                'subscription_plan' => BillingPlan::Pro,
                'subscription_status' => SubscriptionStatus::Active,
            ],
        );

        Organization::query()->updateOrCreate(
            ['slug' => DemoCatalog::ONBOARDING_ORGANIZATION_SLUG],
            [
                'name' => DemoCatalog::ONBOARDING_ORGANIZATION_NAME,
                'country' => 'BE',
                'locale' => 'nl',
                'timezone' => 'Europe/Brussels',
                'selected_plan' => BillingPlan::Starter,
                'subscription_plan' => null,
                'subscription_status' => null,
            ],
        );
    }
}
