<?php

namespace App\Policies;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\MatchEvent;
use App\Models\MatchRefereeAssignment;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\TournamentReferee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TenantRecordPolicy
{
    /**
     * @var array<int, class-string<Model>>
     */
    private array $eventManagedModels = [
        Event::class,
        Tournament::class,
        TournamentEntry::class,
        TournamentMatch::class,
        MatchResult::class,
        MatchEvent::class,
        TournamentReferee::class,
        MatchRefereeAssignment::class,
    ];

    public function view(User $user, Model $model): bool
    {
        $organizationId = $this->organizationId($model);

        if ($organizationId === null) {
            return false;
        }

        return $user->belongsToOrganizationId($organizationId);
    }

    public function manage(User $user, Model $model): bool
    {
        $organizationId = $this->organizationId($model);

        if ($organizationId === null) {
            return false;
        }

        if ($user->hasOrganizationRole($organizationId, OrganizationRole::OrganizationAdmin)) {
            return true;
        }

        if ($this->isEventManagedModel($model)) {
            return $user->hasOrganizationRole($organizationId, OrganizationRole::EventManager);
        }

        return false;
    }

    public function create(User $user, string $modelClass): bool
    {
        $organizationId = $user->currentOrganization()?->id;

        if ($organizationId === null) {
            return false;
        }

        if ($user->hasOrganizationRole($organizationId, OrganizationRole::OrganizationAdmin)) {
            return true;
        }

        return is_a($modelClass, Model::class, true)
            && $this->isEventManagedModelClass($modelClass)
            && $user->hasOrganizationRole($organizationId, OrganizationRole::EventManager);
    }

    public function manageEventOperations(User $user, Model $model): bool
    {
        $organizationId = $this->organizationId($model);

        if ($organizationId === null) {
            return false;
        }

        return $user->hasOrganizationRole(
            $organizationId,
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::EventManager,
        );
    }

    public function manageMatchScoring(User $user, TournamentMatch $match): bool
    {
        $organizationId = $this->organizationId($match);

        if ($organizationId === null) {
            return false;
        }

        return $user->hasOrganizationRole(
            $organizationId,
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::EventManager,
            OrganizationRole::Scorekeeper,
        );
    }

    private function organizationId(Model $model): ?int
    {
        if (! $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'organization_id')) {
            return null;
        }

        $organizationId = $model->getAttribute('organization_id');

        if (! is_numeric($organizationId)) {
            return null;
        }

        return (int) $organizationId;
    }

    private function isEventManagedModel(Model $model): bool
    {
        return $this->isEventManagedModelClass($model::class);
    }

    private function isEventManagedModelClass(string $modelClass): bool
    {
        foreach ($this->eventManagedModels as $eventManagedModel) {
            if (is_a($modelClass, $eventManagedModel, true)) {
                return true;
            }
        }

        return false;
    }
}
