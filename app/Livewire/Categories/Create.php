<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create category')]
class Create extends Component
{
    public string $name = '';

    public ?int $sport_id = null;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Category::class);
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        Category::query()->create([
            'organization_id' => $organization->id,
            'sport_id' => $validated['sport_id'],
            'name' => $validated['name'],
            'slug' => $this->makeUniqueSlug($validated['name'], $organization->id),
        ]);

        $this->redirect(route('categories.index', absolute: false));
    }

    #[Computed]
    public function sports()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Sport::query()
            ->forOrganization($organization)
            ->orderBy('name')
            ->get();
    }

    private function makeUniqueSlug(string $name, int $organizationId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Category::query()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
