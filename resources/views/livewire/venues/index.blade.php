<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Venues') }}</flux:heading>
            <flux:subheading>{{ __('Manage tournament venues') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('venues.create')" wire:navigate>{{ __('Create venue') }}</flux:button>
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
                    <th class="px-4 py-3">{{ __('Address') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->venues as $venue)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $venue->name }}</td>
                        <td class="px-4 py-3">{{ $venue->address ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('venues.edit', $venue)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deleteVenue({{ $venue->id }})" wire:confirm="{{ __('Delete this venue?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="3">{{ __('No venues yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
