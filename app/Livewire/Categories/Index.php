<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Categories')]
class Index extends Component
{
    public function deleteCategory(int $categoryId): void
    {
        $category = Category::query()->withCount(['teams', 'tournaments'])->findOrFail($categoryId);

        Gate::authorize('manage-tenant-record', $category);

        if ($category->teams_count > 0 || $category->tournaments_count > 0) {
            $this->addError('delete', 'This category is in use and cannot be deleted.');

            return;
        }

        $category->delete();
        session()->flash('status', 'Category deleted successfully.');
    }

    #[Computed]
    public function categories()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Category::query()
            ->forOrganization($organization)
            ->with('sport')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.categories.index');
    }
}
