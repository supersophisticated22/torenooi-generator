<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Services\OnboardingFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutSuccessController extends Controller
{
    public function __invoke(Request $request, OnboardingFlow $onboardingFlow): RedirectResponse
    {
        $user = $request->user();
        $organization = $user?->currentOrganization();

        if ($user === null || $organization === null) {
            return redirect()->route('onboarding.organization');
        }

        if ($onboardingFlow->isOrganizationSubscribed($organization)) {
            $onboardingFlow->markComplete($user);

            return redirect()->route('dashboard')->with('status', 'Subscription active. Welcome onboard.');
        }

        $onboardingFlow->markCheckoutPending($user);

        return redirect()->route('onboarding.payment')->with('status', 'Checkout completed. Waiting for Stripe confirmation.');
    }
}
