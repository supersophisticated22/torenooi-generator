<?php

namespace App\Livewire\Events;

use App\Domain\Tournaments\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit event')]
class Edit extends Component
{
    #[Locked]
    public int $eventId;

    public string $name = '';

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public string $status = 'draft';

    public function mount(Event $event): void
    {
        Gate::authorize('manage-tenant-record', $event);

        $this->eventId = $event->id;
        $this->name = $event->name;
        $this->starts_at = $event->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $event->ends_at?->format('Y-m-d\TH:i');
        $this->status = $event->status->value;
    }

    public function save(): void
    {
        $event = Event::query()->findOrFail($this->eventId);
        Gate::authorize('manage-tenant-record', $event);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:'.implode(',', array_column(EventStatus::cases(), 'value'))],
        ]);

        $event->update([
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
