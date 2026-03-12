<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Tournaments') }}</flux:heading>
            <flux:subheading>{{ __('Manage tournaments and entries') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('tournaments.create')" wire:navigate>{{ __('Create tournament') }}</flux:button>
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
                    <th class="px-4 py-3">{{ __('Event') }}</th>
                    <th class="px-4 py-3">{{ __('Sport') }}</th>
                    <th class="px-4 py-3">{{ __('Type') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->tournaments as $tournament)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $tournament->name }}</td>
                        <td class="px-4 py-3">{{ $tournament->event?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $tournament->sport?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $tournament->type->value)) }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $tournament->status->value)) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('tournaments.show', $tournament)" wire:navigate>{{ __('Manage') }}</flux:button>
                                <flux:button size="sm" :href="route('tournaments.show', ['tournament' => $tournament, 'tab' => 'entries'])" wire:navigate>{{ __('Entries') }}</flux:button>
                                <flux:button size="sm" :href="route('tournaments.edit', $tournament)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deleteTournament({{ $tournament->id }})" wire:confirm="{{ __('Delete this tournament?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="6">{{ __('No tournaments yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
