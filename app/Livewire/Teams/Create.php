<?php

namespace App\Livewire\Teams;

use App\Domain\Billing\Exceptions\FeatureLimitExceededException;
use App\Domain\Billing\Services\EnsureOrganizationCanUseFeature;
use App\Models\Category;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create team')]
class Create extends Component
{
    public string $name = '';

    public ?string $short_name = null;

    public ?int $sport_id = null;

    public ?int $category_id = null;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Team::class);
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        try {
            app(EnsureOrganizationCanUseFeature::class)->forTeamCreation($organization);
        } catch (FeatureLimitExceededException $exception) {
            $this->addError('plan', $exception->getMessage());

            return;
        }

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

        Team::query()->create([
            'organization_id' => $organization->id,
            'sport_id' => $validated['sport_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'short_name' => $validated['short_name'],
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
