<section class="w-full max-w-4xl space-y-8">
    <div>
        <flux:heading>{{ __('Match score entry') }}</flux:heading>
        <flux:subheading>
            {{ $this->match->homeTeam?->name ?? __('Home') }}
            {{ __('vs') }}
            {{ $this->match->awayTeam?->name ?? __('Away') }}
        </flux:subheading>
    </div>

    @if (session('status'))
        <flux:text class="font-medium !text-green-600 !dark:text-green-400">{{ session('status') }}</flux:text>
    @endif

    @error('result')
        <flux:text class="font-medium !text-red-600 !dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <form wire:submit="saveScore" class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="sm">{{ __('Score') }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="home_score" :label="__($this->match->homeTeam?->name ?? 'Home score')" type="number" min="0" required />
            <flux:input wire:model="away_score" :label="__($this->match->awayTeam?->name ?? 'Away score')" type="number" min="0" required />
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save score') }}</flux:button>
            <flux:text>{{ __('Current status:') }} {{ ucfirst(str_replace('_', ' ', $this->match->status->value)) }}</flux:text>
        </div>
    </form>

    <form wire:submit="addEvent" class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="sm">{{ __('Add match event') }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-4">
            <flux:select wire:model="event_type" :label="__('Event type')" required>
                @foreach ($this->eventTypeOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="team_id" :label="__('Team')">
                <option value="">{{ __('No team') }}</option>
                @foreach ($this->teamOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="minute" :label="__('Minute')" type="number" min="0" max="300" />
            <flux:input wire:model="notes" :label="__('Notes')" type="text" />
        </div>

        <flux:button variant="primary" type="submit">{{ __('Add event') }}</flux:button>
    </form>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="sm">{{ __('Events') }}</flux:heading>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Type') }}</th>
                    <th class="px-4 py-3">{{ __('Team') }}</th>
                    <th class="px-4 py-3">{{ __('Minute') }}</th>
                    <th class="px-4 py-3">{{ __('Notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->match->events as $event)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $event->event_type->value)) }}</td>
                        <td class="px-4 py-3">{{ $event->team?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $event->minute ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $event->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="4">{{ __('No match events yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center gap-3">
        <flux:button variant="primary" wire:click="completeMatch">{{ __('Mark completed') }}</flux:button>
        <flux:button :href="route('tournaments.index')" wire:navigate>{{ __('Back to tournaments') }}</flux:button>
    </div>
</section>
