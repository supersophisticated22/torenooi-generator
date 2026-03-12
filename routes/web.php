<?php

use App\Http\Controllers\Billing\CreateBillingPortalSessionController;
use App\Http\Controllers\Billing\CreateCheckoutSessionController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Onboarding\CheckoutCancelController;
use App\Http\Controllers\Onboarding\CheckoutSuccessController;
use App\Http\Controllers\Onboarding\StartCheckoutController;
use App\Livewire\Categories\Create as CategoriesCreate;
use App\Livewire\Categories\Edit as CategoriesEdit;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Events\Create as EventsCreate;
use App\Livewire\Events\Edit as EventsEdit;
use App\Livewire\Events\Index as EventsIndex;
use App\Livewire\Fields\Create as FieldsCreate;
use App\Livewire\Fields\Edit as FieldsEdit;
use App\Livewire\Fields\Index as FieldsIndex;
use App\Livewire\Matches\Score as MatchScore;
use App\Livewire\Onboarding\OrganizationCreate as OnboardingOrganizationCreate;
use App\Livewire\Onboarding\Payment as OnboardingPayment;
use App\Livewire\Onboarding\PlanSelect as OnboardingPlanSelect;
use App\Livewire\Players\Create as PlayersCreate;
use App\Livewire\Players\Edit as PlayersEdit;
use App\Livewire\Players\Index as PlayersIndex;
use App\Livewire\Referees\Create as RefereesCreate;
use App\Livewire\Referees\Edit as RefereesEdit;
use App\Livewire\Referees\Index as RefereesIndex;
use App\Livewire\ScoreScreen\Event as PublicEventScreen;
use App\Livewire\ScoreScreen\Index as PublicScoreScreen;
use App\Livewire\ScoreScreen\Tournament as PublicTournamentScreen;
use App\Livewire\Sports\Create as SportsCreate;
use App\Livewire\Sports\Edit as SportsEdit;
use App\Livewire\Sports\Index as SportsIndex;
use App\Livewire\Teams\Create as TeamsCreate;
use App\Livewire\Teams\Edit as TeamsEdit;
use App\Livewire\Teams\Index as TeamsIndex;
use App\Livewire\Teams\Players as TeamsPlayers;
use App\Livewire\Tournaments\Create as TournamentsCreate;
use App\Livewire\Tournaments\Edit as TournamentsEdit;
use App\Livewire\Tournaments\Entries as TournamentsEntries;
use App\Livewire\Tournaments\Index as TournamentsIndex;
use App\Livewire\Tournaments\Show as TournamentsShow;
use App\Livewire\Venues\Create as VenuesCreate;
use App\Livewire\Venues\Edit as VenuesEdit;
use App\Livewire\Venues\Index as VenuesIndex;
use App\Services\OnboardingFlow;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('scores/{organization:slug}', PublicScoreScreen::class)->name('scores.public');
Route::livewire('scores/{organization:slug}/events/{eventSlug}', PublicEventScreen::class)->name('scores.public.event');
Route::livewire('scores/{organization:slug}/tournaments/{tournament}', PublicTournamentScreen::class)->name('scores.public.tournament');
Route::livewire('events/{organization:slug}/{eventSlug}', PublicEventScreen::class)->name('events.public.show');

Route::middleware(['auth'])->group(function () {
    Route::get('onboarding', function (Request $request, OnboardingFlow $onboardingFlow) {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $onboardingFlow->sync($user);
        $requiredRoute = $onboardingFlow->requiredRoute($user);

        return $requiredRoute === null
            ? redirect()->route('dashboard')
            : redirect()->route($requiredRoute);
    })->name('onboarding.index');
    Route::livewire('onboarding/organization', OnboardingOrganizationCreate::class)->name('onboarding.organization');
    Route::livewire('onboarding/plan', OnboardingPlanSelect::class)->name('onboarding.plan');
    Route::livewire('onboarding/payment', OnboardingPayment::class)->name('onboarding.payment');
    Route::post('onboarding/checkout/start', StartCheckoutController::class)->name('onboarding.checkout.start');
    Route::get('onboarding/checkout/success', CheckoutSuccessController::class)->name('onboarding.checkout.success');
    Route::get('onboarding/checkout/cancel', CheckoutCancelController::class)->name('onboarding.checkout.cancel');
});

Route::middleware(['auth', 'organization', 'verified', 'onboarding'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::livewire('sports', SportsIndex::class)->name('sports.index');
    Route::livewire('sports/create', SportsCreate::class)->name('sports.create');
    Route::livewire('sports/{sport}/edit', SportsEdit::class)->name('sports.edit');

    Route::livewire('categories', CategoriesIndex::class)->name('categories.index');
    Route::livewire('categories/create', CategoriesCreate::class)->name('categories.create');
    Route::livewire('categories/{category}/edit', CategoriesEdit::class)->name('categories.edit');

    Route::livewire('teams', TeamsIndex::class)->name('teams.index');
    Route::livewire('teams/create', TeamsCreate::class)->name('teams.create');
    Route::livewire('teams/{team}/edit', TeamsEdit::class)->name('teams.edit');
    Route::livewire('teams/{team}/players', TeamsPlayers::class)->name('teams.players');

    Route::livewire('players', PlayersIndex::class)->name('players.index');
    Route::livewire('players/create', PlayersCreate::class)->name('players.create');
    Route::livewire('players/{player}/edit', PlayersEdit::class)->name('players.edit');

    Route::livewire('referees', RefereesIndex::class)->name('referees.index');
    Route::livewire('referees/create', RefereesCreate::class)->name('referees.create');
    Route::livewire('referees/{referee}/edit', RefereesEdit::class)->name('referees.edit');

    Route::livewire('venues', VenuesIndex::class)->name('venues.index');
    Route::livewire('venues/create', VenuesCreate::class)->name('venues.create');
    Route::livewire('venues/{venue}/edit', VenuesEdit::class)->name('venues.edit');

    Route::livewire('fields', FieldsIndex::class)->name('fields.index');
    Route::livewire('fields/create', FieldsCreate::class)->name('fields.create');
    Route::livewire('fields/{field}/edit', FieldsEdit::class)->name('fields.edit');
    Route::livewire('matches/{match}/score', MatchScore::class)->name('matches.score');

    Route::livewire('events', EventsIndex::class)->name('events.index');
    Route::livewire('events/create', EventsCreate::class)->name('events.create');
    Route::livewire('events/{event}/edit', EventsEdit::class)->name('events.edit');

    Route::livewire('tournaments', TournamentsIndex::class)->name('tournaments.index');
    Route::livewire('tournaments/create', TournamentsCreate::class)->name('tournaments.create');
    Route::livewire('tournaments/{tournament}', TournamentsShow::class)->name('tournaments.show');
    Route::livewire('tournaments/{tournament}/edit', TournamentsEdit::class)->name('tournaments.edit');
    Route::livewire('tournaments/{tournament}/entries', TournamentsEntries::class)->name('tournaments.entries');

    Route::post('billing/checkout/{plan}', CreateCheckoutSessionController::class)->name('billing.checkout');
    Route::post('billing/portal', CreateBillingPortalSessionController::class)->name('billing.portal');
});

Route::post('stripe/webhook', StripeWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('stripe.webhook');

require __DIR__.'/settings.php';
