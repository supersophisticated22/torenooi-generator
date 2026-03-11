<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TenantRecordPolicy
{
    public function manage(User $user, Model $model): bool
    {
        if (! $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'organization_id')) {
            return false;
        }

        return $user->belongsToOrganizationId((int) $model->getAttribute('organization_id'));
    }
}
