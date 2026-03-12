<?php

namespace App\Services;

use App\Models\Organization;

class OrganizationSettings
{
    public function for(Organization $organization): array
    {
        return [
            'name' => $organization->name,
            'slug' => $organization->slug,
            'logo_path' => $organization->logo_path,
            'primary_color' => $organization->primary_color,
            'timezone' => $organization->timezone,
            'locale' => $organization->locale,
        ];
    }
}
