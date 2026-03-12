<?php

namespace App\Livewire\Sports;

use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create sport')]
class Create extends Component
{
    public string $name = '';

    public int $win_points = 3;

    public int $draw_points = 1;

    public int $loss_points = 0;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Sport::class);
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'win_points' => ['required', 'integer', 'min:0'],
            'draw_points' => ['required', 'integer', 'min:0'],
            'loss_points' => ['required', 'integer', 'min:0'],
        ]);

        $slug = $this->makeUniqueSlug($validated['name'], $organization->id);

        $sport = Sport::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        $sport->sportRule()->create([
            'organization_id' => $organization->id,
            'win_points' => $validated['win_points'],
            'draw_points' => $validated['draw_points'],
            'loss_points' => $validated['loss_points'],
        ]);

        $this->redirect(route('sports.index', absolute: false));
    }

    private function makeUniqueSlug(string $name, int $organizationId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Sport::query()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
