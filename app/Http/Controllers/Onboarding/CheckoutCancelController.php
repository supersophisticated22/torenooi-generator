<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutCancelController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $user->forceFill(['onboarding_status' => OnboardingStatus::PlanSelected])->save();
        }

        return redirect()->route('onboarding.plan')->with('warning', 'Checkout was canceled. You can choose a plan and try again.');
    }
}
