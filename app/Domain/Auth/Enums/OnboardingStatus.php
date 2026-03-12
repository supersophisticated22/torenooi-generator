<?php

namespace App\Domain\Auth\Enums;

enum OnboardingStatus: string
{
    case AccountCreated = 'account_created';
    case OrganizationCreated = 'organization_created';
    case PlanSelected = 'plan_selected';
    case CheckoutPending = 'checkout_pending';
    case Subscribed = 'subscribed';
    case OnboardingComplete = 'onboarding_complete';
}
