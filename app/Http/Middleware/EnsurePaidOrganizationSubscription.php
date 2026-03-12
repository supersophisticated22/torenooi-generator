<?php

namespace App\Http\Middleware;

use App\Domain\Billing\Services\SubscriptionLimits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaidOrganizationSubscription
{
    public function __construct(private SubscriptionLimits $subscriptionLimits) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->user()?->currentOrganization();

        if ($organization === null) {
            return redirect()->route('onboarding.organization');
        }

        if (! $this->subscriptionLimits->hasPaidSubscription($organization)) {
            return redirect()->route('billing.show')->with('warning', 'User management is available on paid plans only.');
        }

        return $next($request);
    }
}
