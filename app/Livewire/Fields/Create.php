<?php

namespace App\Livewire\Fields;

use App\Models\Field;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create field')]
class Create extends Component
{
    public string $name = '';

    public ?string $code = null;

    public ?int $venue_id = null;

    public ?int $sport_id = null;

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'venue_id' => ['required', Rule::exists('venues', 'id')->where('organization_id', $organization->id)],
            'sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        Field::query()->create([
            'organization_id' => $organization->id,
            'venue_id' => $validated['venue_id'],
            'sport_id' => $validated['sport_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
        ]);

        $this->redirect(route('fields.index', absolute: false));
    }

    #[Computed]
    public function venues()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Venue::query()->forOrganization($organization)->orderBy('name')->get();
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
}
