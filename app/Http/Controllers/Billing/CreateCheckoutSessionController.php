<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\CreateCheckoutSession;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CreateCheckoutSessionController extends Controller
{
    public function __invoke(Request $request, string $plan, CreateCheckoutSession $createCheckoutSession): RedirectResponse
    {
        Gate::authorize('manage-organization-billing');

        $organization = $request->user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $billingPlan = BillingPlan::tryFrom($plan);

        if ($billingPlan === null) {
            abort(404);
        }

        if ($billingPlan === BillingPlan::Free) {
            $organization->forceFill([
                'selected_plan' => BillingPlan::Free,
                'subscription_plan' => BillingPlan::Free,
                'subscription_status' => SubscriptionStatus::Active,
                'subscription_ends_at' => null,
            ])->save();

            return redirect()->route('billing.show')->with('status', 'Free plan activated.');
        }

        try {
            $session = $createCheckoutSession(
                organization: $organization,
                plan: $billingPlan,
                successUrl: route('billing.show', ['checkout' => 'success']),
                cancelUrl: route('billing.show', ['checkout' => 'canceled']),
            );
        } catch (BillingException $exception) {
            return back()->withErrors([
                'billing' => $exception->getMessage(),
            ]);
        }

        return redirect()->away($session->url);
    }
}
