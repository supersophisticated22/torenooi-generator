<?php

namespace App\Http\Middleware;

use App\Services\OnboardingFlow;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function __construct(private OnboardingFlow $onboardingFlow) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $this->onboardingFlow->sync($user);

        $requiredRoute = $this->onboardingFlow->requiredRoute($user);

        if ($requiredRoute !== null && ! $request->routeIs($requiredRoute)) {
            return redirect()->route($requiredRoute);
        }

        return $next($request);
    }
}
