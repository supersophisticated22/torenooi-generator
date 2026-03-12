<?php

namespace App\Livewire\Teams;

use App\Models\Category;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit team')]
class Edit extends Component
{
    #[Locked]
    public int $teamId;

    public string $name = '';

    public ?string $short_name = null;

    public ?int $sport_id = null;

    public ?int $category_id = null;

    public function mount(Team $team): void
    {
        Gate::authorize('manage-tenant-record', $team);

        $this->teamId = $team->id;
        $this->name = $team->name;
        $this->short_name = $team->short_name;
        $this->sport_id = $team->sport_id;
        $this->category_id = $team->category_id;
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $team = Team::query()->findOrFail($this->teamId);
        Gate::authorize('manage-tenant-record', $team);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:32'],
            'sport_id' => ['required', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organization->id)
                    ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $this->sport_id)),
            ],
        ]);

        $team->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'],
            'sport_id' => $validated['sport_id'],
            'category_id' => $validated['category_id'],
        ]);

        $this->redirect(route('teams.index', absolute: false));
    }

    #[Computed]
    public function sports()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Sport::query()->forOrganization($organization)->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Category::query()->forOrganization($organization)->orderBy('name')->get();
    }
}
