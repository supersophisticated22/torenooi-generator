<section class="w-full max-w-3xl">
    <flux:heading>{{ __('Team players') }}: {{ $this->team->name }}</flux:heading>
    <flux:subheading>{{ __('Assign players and jersey numbers') }}</flux:subheading>

    <form wire:submit="assignPlayer" class="mt-6 grid gap-4 sm:grid-cols-3">
        <flux:select wire:model="player_id" :label="__('Player')" required>
            <option value="">{{ __('Select player') }}</option>
            @foreach ($this->availablePlayers as $player)
                <option value="{{ $player->id }}">{{ $player->number ? '#'.$player->number.' - ' : '' }}{{ $player->first_name }} {{ $player->last_name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model="jersey_number" :label="__('Jersey number')" type="number" min="0" />

        <div class="flex items-end">
            <flux:button variant="primary" type="submit">{{ __('Assign') }}</flux:button>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Player') }}</th>
                    <th class="px-4 py-3">{{ __('Jersey') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->team->players as $player)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $player->number ? '#'.$player->number.' - ' : '' }}{{ $player->first_name }} {{ $player->last_name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:input wire:model="jerseyNumbers.{{ $player->id }}" type="number" min="0" class="max-w-28" />
                                <flux:button size="sm" wire:click="updateJerseyNumber({{ $player->id }})">{{ __('Save') }}</flux:button>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:button size="sm" variant="danger" wire:click="removePlayer({{ $player->id }})">{{ __('Remove') }}</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="3">{{ __('No players assigned yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
