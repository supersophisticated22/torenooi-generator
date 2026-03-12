<?php

namespace App\Http\Controllers;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\TournamentMatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $organization = $request->user()?->currentOrganization();

        abort_if($organization === null, 403);

        $upcomingMatches = TournamentMatch::query()
            ->where('organization_id', $organization->id)
            ->where('status', MatchStatus::Scheduled->value)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now())
            ->count();

        return view('dashboard', [
            'organization' => $organization,
            'sportsCount' => $organization->sports()->count(),
            'teamsCount' => $organization->teams()->count(),
            'eventsCount' => $organization->events()->count(),
            'tournamentsCount' => $organization->tournaments()->count(),
            'upcomingMatchesCount' => $upcomingMatches,
        ]);
    }
}
