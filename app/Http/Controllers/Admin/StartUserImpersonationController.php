<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StartUserImpersonationController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $impersonator = $request->user();

        if ($impersonator === null || ! $impersonator->isPlatformAdmin()) {
            abort(403);
        }

        if ($impersonator->id === $user->id) {
            return redirect()->route('admin.organizations.index')->with('warning', 'You are already this user.');
        }

        if ($user->isDisabled()) {
            return redirect()->route('admin.users.index')->with('warning', 'Disabled users cannot be impersonated.');
        }

        $request->session()->put('impersonator_id', $impersonator->id);
        $request->session()->put('impersonator_redirect_url', route('admin.organizations.index'));

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'You are now impersonating '.$user->email.'.');
    }
}
