<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToOrganization
{
    public function scopeForOrganization(Builder $query, Organization|int $organization): Builder
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return $query->where($this->qualifyColumn('organization_id'), $organizationId);
    }

    public function scopeForUserOrganization(Builder $query, User $user): Builder
    {
        $organization = $user->currentOrganization();

        if ($organization === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->forOrganization($organization);
    }

    public function belongsToOrganization(User $user): bool
    {
        return $user->belongsToOrganizationId((int) $this->getAttribute('organization_id'));
    }
}
