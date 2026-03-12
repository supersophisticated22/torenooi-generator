<?php

namespace App\Livewire\Events;

use App\Domain\Tournaments\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create event')]
class Create extends Component
{
    public string $name = '';

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public string $status = 'draft';

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Event::class);
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:'.implode(',', array_column(EventStatus::cases(), 'value'))],
        ]);

        Event::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'starts_at' => $this->normalizeDateTime($validated['starts_at']),
            'ends_at' => $this->normalizeDateTime($validated['ends_at']),
            'status' => $validated['status'],
        ]);

        $this->redirect(route('events.index', absolute: false));
    }

    public function statusOptions(): array
    {
        return array_map(fn (EventStatus $status): array => [
            'value' => $status->value,
            'label' => ucfirst(str_replace('_', ' ', $status->value)),
        ], EventStatus::cases());
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
