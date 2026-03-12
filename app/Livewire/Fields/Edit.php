<?php

namespace App\Livewire\Fields;

use App\Models\Field;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit field')]
class Edit extends Component
{
    #[Locked]
    public int $fieldId;

    public string $name = '';

    public ?string $code = null;

    public ?int $venue_id = null;

    public ?int $sport_id = null;

    public function mount(Field $field): void
    {
        Gate::authorize('manage-tenant-record', $field);

        $this->fieldId = $field->id;
        $this->name = $field->name;
        $this->code = $field->code;
        $this->venue_id = $field->venue_id;
        $this->sport_id = $field->sport_id;
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $field = Field::query()->findOrFail($this->fieldId);
        Gate::authorize('manage-tenant-record', $field);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'venue_id' => ['required', Rule::exists('venues', 'id')->where('organization_id', $organization->id)],
            'sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        $field->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'venue_id' => $validated['venue_id'],
            'sport_id' => $validated['sport_id'],
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
