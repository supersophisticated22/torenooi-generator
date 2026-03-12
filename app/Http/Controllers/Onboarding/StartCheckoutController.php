<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Actions\CreateCheckoutSession;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Services\OnboardingFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartCheckoutController extends Controller
{
    public function __invoke(Request $request, CreateCheckoutSession $createCheckoutSession, OnboardingFlow $onboardingFlow): RedirectResponse
    {
        $user = $request->user();
        $organization = $user?->currentOrganization();

        if ($user === null || $organization === null) {
            return redirect()->route('onboarding.organization');
        }

        if (! $user->hasOrganizationRole($organization->id, OrganizationRole::OrganizationAdmin)) {
            abort(403);
        }

        $plan = $organization->selected_plan;

        if (! $plan instanceof BillingPlan) {
            return redirect()->route('onboarding.plan')->with('warning', 'Select a plan before checkout.');
        }

        if ($plan === BillingPlan::Free) {
            $organization->forceFill([
                'subscription_plan' => BillingPlan::Free,
                'subscription_status' => SubscriptionStatus::Active,
            ])->save();

            $onboardingFlow->markComplete($user);

            return redirect()->route('dashboard')->with('status', 'Free plan activated. Welcome onboard.');
        }

        try {
            $session = $createCheckoutSession(
                organization: $organization,
                plan: $plan,
                successUrl: route('onboarding.checkout.success'),
                cancelUrl: route('onboarding.checkout.cancel'),
            );
        } catch (BillingException $exception) {
            return redirect()->route('onboarding.payment')->withErrors([
                'billing' => $exception->getMessage(),
            ]);
        }

        $onboardingFlow->markCheckoutPending($user);

        return redirect()->away($session->url);
    }
}
