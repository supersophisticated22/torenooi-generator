<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopUserImpersonationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        $redirectUrl = $request->session()->pull('impersonator_redirect_url', route('admin.organizations.index'));

        if (! is_int($impersonatorId) && ! (is_string($impersonatorId) && ctype_digit($impersonatorId))) {
            return redirect()->route('dashboard')->with('warning', 'No active impersonation session found.');
        }

        $impersonator = User::query()->find((int) $impersonatorId);

        if ($impersonator === null || ! $impersonator->isPlatformAdmin()) {
            abort(403);
        }

        Auth::login($impersonator);

        return redirect()->to($redirectUrl)->with('status', 'Impersonation ended.');
    }
}
