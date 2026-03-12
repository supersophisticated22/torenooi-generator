<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Events')]
class Index extends Component
{
    public function deleteEvent(int $eventId): void
    {
        $event = Event::query()->withCount('tournaments')->findOrFail($eventId);

        Gate::authorize('manage-tenant-record', $event);

        if ($event->tournaments_count > 0) {
            $this->addError('delete', 'This event has tournaments and cannot be deleted.');

            return;
        }

        $event->delete();
        session()->flash('status', 'Event deleted successfully.');
    }

    #[Computed]
    public function events()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Event::query()
            ->forOrganization($organization)
            ->orderByDesc('starts_at')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.events.index');
    }
}
