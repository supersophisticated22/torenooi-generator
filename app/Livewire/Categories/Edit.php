<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit category')]
class Edit extends Component
{
    #[Locked]
    public int $categoryId;

    public string $name = '';

    public ?int $sport_id = null;

    public function mount(Category $category): void
    {
        Gate::authorize('manage-tenant-record', $category);

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->sport_id = $category->sport_id;
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $category = Category::query()->findOrFail($this->categoryId);
        Gate::authorize('manage-tenant-record', $category);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        $category->update([
            'name' => $validated['name'],
            'sport_id' => $validated['sport_id'],
            'slug' => $this->makeUniqueSlug($validated['name'], $organization->id, $category->id),
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

    private function makeUniqueSlug(string $name, int $organizationId, int $ignoreCategoryId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Category::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->whereKeyNot($ignoreCategoryId)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
