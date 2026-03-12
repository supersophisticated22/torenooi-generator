<?php

namespace App\Domain\Tournaments\Services;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Support\Str;

class GenerateEventSlug
{
    public function forOrganization(Organization $organization, string $name, ?int $ignoreEventId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'event';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($organization, $slug, $ignoreEventId)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(Organization $organization, string $slug, ?int $ignoreEventId = null): bool
    {
        return Event::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->when($ignoreEventId !== null, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->exists();
    }
}
