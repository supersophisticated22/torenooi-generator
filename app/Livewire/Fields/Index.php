<?php

namespace App\Livewire\Fields;

use App\Models\Field;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fields')]
class Index extends Component
{
    public function deleteField(int $fieldId): void
    {
        $field = Field::query()->withCount('matches')->findOrFail($fieldId);

        Gate::authorize('manage-tenant-record', $field);

        if ($field->matches_count > 0) {
            $this->addError('delete', 'This field is in use and cannot be deleted.');

            return;
        }

        $field->delete();
        session()->flash('status', 'Field deleted successfully.');
    }

    #[Computed]
    public function fields()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Field::query()
            ->forOrganization($organization)
            ->with(['venue', 'sport'])
            ->orderBy('name')
            ->get();
    }
}
