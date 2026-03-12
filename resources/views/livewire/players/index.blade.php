<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Players') }}</flux:heading>
            <flux:subheading>{{ __('Manage individual players') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('players.create')" wire:navigate>{{ __('Create player') }}</flux:button>
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
                    <th class="px-4 py-3">{{ __('Email') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->players as $player)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $player->first_name }} {{ $player->last_name }}</td>
                        <td class="px-4 py-3">{{ $player->email ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('players.edit', $player)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deletePlayer({{ $player->id }})" wire:confirm="{{ __('Delete this player?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="3">{{ __('No players yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
