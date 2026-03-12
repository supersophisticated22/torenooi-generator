<?php

namespace App\Services;

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\User;

class OnboardingFlow
{
    public function sync(User $user): OnboardingStatus
    {
        $organization = $user->currentOrganization();

        if ($organization === null) {
            return $this->updateStatus($user, OnboardingStatus::AccountCreated);
        }

        if ($this->isOrganizationSubscribed($organization)) {
            return $this->updateStatus($user, OnboardingStatus::OnboardingComplete);
        }

        if ($organization->selected_plan === null) {
            return $this->updateStatus($user, OnboardingStatus::OrganizationCreated);
        }

        if (! in_array($user->onboarding_status, [OnboardingStatus::CheckoutPending, OnboardingStatus::Subscribed], true)) {
            return $this->updateStatus($user, OnboardingStatus::PlanSelected);
        }

        return $user->onboarding_status;
    }

    public function requiredRoute(User $user): ?string
    {
        $organization = $user->currentOrganization();

        if ($organization === null) {
            return 'onboarding.organization';
        }

        if ($this->isOrganizationSubscribed($organization)) {
            return null;
        }

        if ($organization->selected_plan === null) {
            return 'onboarding.plan';
        }

        return 'onboarding.payment';
    }

    public function markCheckoutPending(User $user): void
    {
        $this->updateStatus($user, OnboardingStatus::CheckoutPending);
    }

    public function markSubscribed(User $user): void
    {
        $this->updateStatus($user, OnboardingStatus::Subscribed);
    }

    public function markComplete(User $user): void
    {
        $this->updateStatus($user, OnboardingStatus::OnboardingComplete);
    }

    public function isOrganizationSubscribed(Organization $organization): bool
    {
        return in_array($organization->subscription_status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true);
    }

    private function updateStatus(User $user, OnboardingStatus $status): OnboardingStatus
    {
        if ($user->onboarding_status !== $status) {
            $user->forceFill(['onboarding_status' => $status])->save();
        }

        return $status;
    }
}
