<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\CreateBillingPortalSession;
use App\Domain\Billing\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CreateBillingPortalSessionController extends Controller
{
    public function __invoke(Request $request, CreateBillingPortalSession $createBillingPortalSession): RedirectResponse
    {
        Gate::authorize('manage-organization-billing');

        $organization = $request->user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        try {
            $url = $createBillingPortalSession($organization, route('billing.show'));
        } catch (BillingException $exception) {
            return back()->withErrors([
                'billing' => $exception->getMessage(),
            ]);
        }

        return redirect()->away($url);
    }
}
