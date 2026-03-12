<?php

namespace App\Livewire\Sports;

use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit sport')]
class Edit extends Component
{
    #[Locked]
    public int $sportId;

    public string $name = '';

    public int $win_points = 3;

    public int $draw_points = 1;

    public int $loss_points = 0;

    public function mount(Sport $sport): void
    {
        Gate::authorize('manage-tenant-record', $sport);

        $this->sportId = $sport->id;
        $this->name = $sport->name;
        $this->win_points = $sport->sportRule?->win_points ?? 3;
        $this->draw_points = $sport->sportRule?->draw_points ?? 1;
        $this->loss_points = $sport->sportRule?->loss_points ?? 0;
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $sport = Sport::query()->findOrFail($this->sportId);
        Gate::authorize('manage-tenant-record', $sport);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'win_points' => ['required', 'integer', 'min:0'],
            'draw_points' => ['required', 'integer', 'min:0'],
            'loss_points' => ['required', 'integer', 'min:0'],
        ]);

        $slug = $this->makeUniqueSlug($validated['name'], $organization->id, $sport->id);

        $sport->update([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        $sport->sportRule()->updateOrCreate(
            ['sport_id' => $sport->id],
            [
                'organization_id' => $organization->id,
                'win_points' => $validated['win_points'],
                'draw_points' => $validated['draw_points'],
                'loss_points' => $validated['loss_points'],
            ],
        );

        $this->redirect(route('sports.index', absolute: false));
    }

    private function makeUniqueSlug(string $name, int $organizationId, int $ignoreSportId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Sport::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->whereKeyNot($ignoreSportId)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
