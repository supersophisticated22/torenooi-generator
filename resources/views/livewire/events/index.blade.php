<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Events') }}</flux:heading>
            <flux:subheading>{{ __('Manage tournament events') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('events.create')" wire:navigate>{{ __('Create event') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:text class="mt-4 font-medium !text-green-600 !dark:text-green-400">{{ session('status') }}</flux:text>
    @endif

    @error('delete')
        <flux:text class="mt-4 font-medium !text-red-600 !dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <div class="mt-6 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Start') }}</th>
                    <th class="px-4 py-3">{{ __('End') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->events as $event)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $event->name }}</td>
                        <td class="px-4 py-3">{{ $event->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $event->ends_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $event->status->value)) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('events.edit', $event)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deleteEvent({{ $event->id }})" wire:confirm="{{ __('Delete this event?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="5">{{ __('No events yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
