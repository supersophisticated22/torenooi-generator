<section class="w-full max-w-3xl">
    <flux:heading>{{ __('Tournament entries') }}: {{ $this->tournament->name }}</flux:heading>
    <flux:subheading>{{ __('Attach teams to this tournament') }}</flux:subheading>
    <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-400">
        {{ __('Seed sets ranking in the draw. Lower numbers (1, 2, 3) are higher-ranked participants and are placed to avoid meeting too early. Leave empty if you do not use seeding.') }}
    </p>

    <form wire:submit="addTeam" class="mt-6 grid gap-4 sm:grid-cols-3">
        <flux:select wire:model="team_id" :label="__('Team')" required>
            <option value="">{{ __('Select team') }}</option>
            @foreach ($this->availableTeams as $team)
                <option value="{{ $team->id }}">{{ $team->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model="seed" :label="__('Seed (optional)')" type="number" min="1" />

        <div class="flex items-end">
            <flux:button variant="primary" type="submit">{{ __('Add team') }}</flux:button>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Team') }}</th>
                    <th class="px-4 py-3">{{ __('Seed') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->tournament->entries as $entry)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $entry->team?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:input wire:model="seeds.{{ $entry->id }}" type="number" min="1" class="max-w-28" />
                                <flux:button size="sm" wire:click="updateSeed({{ $entry->id }})">{{ __('Save') }}</flux:button>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:button size="sm" variant="danger" wire:click="removeEntry({{ $entry->id }})" wire:confirm="{{ __('Remove this entry?') }}">{{ __('Remove') }}</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="3">{{ __('No entries yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
