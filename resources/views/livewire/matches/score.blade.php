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

    <div class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div class="flex flex-wrap items-end gap-3">
            <flux:select wire:model="referee_id" :label="__('Assign referee')" class="min-w-60">
                <option value="">{{ __('Select referee') }}</option>
                @foreach ($this->availableReferees() as $referee)
                    <option value="{{ $referee->id }}">
                        {{ $referee->first_name }} {{ $referee->last_name }}
                        @if ($referee->sport)
                            ({{ $referee->sport->name }})
                        @endif
                    </option>
                @endforeach
            </flux:select>
            <flux:button variant="primary" wire:click="assignReferee">{{ __('Assign') }}</flux:button>
        </div>

        @error('referee_id')
            <flux:text class="font-medium !text-red-600 !dark:text-red-400">{{ $message }}</flux:text>
        @enderror

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                    <tr>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Sport') }}</th>
                        <th class="px-4 py-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->match->referees as $referee)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="px-4 py-3">{{ $referee->first_name }} {{ $referee->last_name }}</td>
                            <td class="px-4 py-3">{{ $referee->sport?->name ?? __('All sports') }}</td>
                            <td class="px-4 py-3">
                                <flux:button variant="ghost" size="sm" wire:click="removeReferee({{ $referee->id }})">{{ __('Remove') }}</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-neutral-500" colspan="3">{{ __('No referees assigned yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form wire:submit="addEvent" class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="sm">{{ __('Add match event') }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-6">
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

            <flux:select wire:model="player_id" :label="__('Player')">
                <option value="">{{ __('No player') }}</option>
                @foreach ($this->playerOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="minute" :label="__('Minute')" type="number" min="0" max="300" />
            <flux:input wire:model="sequence" :label="__('Sequence')" type="number" min="1" max="2000" />
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
                    <th class="px-4 py-3">{{ __('Player') }}</th>
                    <th class="px-4 py-3">{{ __('Minute') }}</th>
                    <th class="px-4 py-3">{{ __('Sequence') }}</th>
                    <th class="px-4 py-3">{{ __('Notes') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->match->events as $event)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $event->event_type->value)) }}</td>
                        <td class="px-4 py-3">{{ $event->team?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($event->player)
                                {{ trim($event->player->first_name.' '.$event->player->last_name) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $event->minute ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $event->sequence ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $event->notes ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <flux:button variant="ghost" size="sm" wire:click="removeEvent({{ $event->id }})">{{ __('Remove') }}</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="7">{{ __('No match events yet.') }}</td>
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
