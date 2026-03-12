<?php

namespace App\Providers;

use App\Models\TournamentMatch;
use App\Models\User;
use App\Policies\TenantRecordPolicy;
use App\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentOrganization::class);
        $this->app->singleton(TenantRecordPolicy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureAuthorization(): void
    {
        Gate::define('view-tenant-record', function (User $user, Model $model): bool {
            return app(TenantRecordPolicy::class)->view($user, $model);
        });

        Gate::define('manage-tenant-record', function (User $user, Model $model): bool {
            return app(TenantRecordPolicy::class)->manage($user, $model);
        });

        Gate::define('create-tenant-record', function (User $user, string $modelClass): bool {
            return app(TenantRecordPolicy::class)->create($user, $modelClass);
        });

        Gate::define('manage-event-operations', function (User $user, Model $model): bool {
            return app(TenantRecordPolicy::class)->manageEventOperations($user, $model);
        });

        Gate::define('manage-match-scoring', function (User $user, TournamentMatch $match): bool {
            return app(TenantRecordPolicy::class)->manageMatchScoring($user, $match);
        });
    }
}
