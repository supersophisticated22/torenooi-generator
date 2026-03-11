<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentOrganization
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $this->currentOrganization->set($user?->currentOrganization());

        return $next($request);
    }
}
