<section class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading>{{ $this->tournament->name }}</flux:heading>
            <flux:subheading>
                {{ $this->tournament->event?->name ?? '-' }}
                <span class="mx-1">•</span>
                {{ $this->tournament->sport?->name ?? '-' }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('tournaments.edit', $this->tournament)" wire:navigate>{{ __('Edit settings') }}</flux:button>
            <flux:button variant="primary" :href="route('tournaments.index')" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:text class="font-medium !text-green-600 !dark:text-green-400">{{ session('status') }}</flux:text>
    @endif

    @error('generate')
        <flux:text class="font-medium !text-red-600 !dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <div class="flex flex-wrap gap-2">
        <flux:button :variant="$tab === 'settings' ? 'primary' : 'filled'" wire:click="setTab('settings')">{{ __('Settings') }}</flux:button>
        <flux:button :variant="$tab === 'entries' ? 'primary' : 'filled'" wire:click="setTab('entries')">{{ __('Entries') }}</flux:button>
        <flux:button :variant="$tab === 'matches' ? 'primary' : 'filled'" wire:click="setTab('matches')">{{ __('Generated matches') }}</flux:button>
        <flux:button :variant="$tab === 'standings' ? 'primary' : 'filled'" wire:click="setTab('standings')">{{ __('Standings') }}</flux:button>
        <flux:button :variant="$tab === 'scores' ? 'primary' : 'filled'" wire:click="setTab('scores')">{{ __('Score entry') }}</flux:button>
    </div>

    @if ($tab === 'settings')
        <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Type') }}</dt>
                    <dd class="mt-1 font-medium">{{ ucfirst(str_replace('_', ' ', $this->tournament->type->value)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Final type') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->final_type ? ucfirst(str_replace('_', ' ', $this->tournament->final_type->value)) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Status') }}</dt>
                    <dd class="mt-1 font-medium">{{ ucfirst(str_replace('_', ' ', $this->tournament->status->value)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Pools') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->pool_count }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Match duration') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->match_duration_minutes ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Break') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->break_duration_minutes ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Final break') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->final_break_minutes ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Scheduled start') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->scheduled_start_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Category') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->tournament->category?->name ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    @endif

    @if ($tab === 'entries')
        <div class="space-y-4 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Tournament entries') }}</flux:heading>
                <flux:button :href="route('tournaments.entries', $this->tournament)" wire:navigate>{{ __('Manage entries') }}</flux:button>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Team') }}</th>
                            <th class="px-4 py-3">{{ __('Seed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->tournament->entries->sortBy('seed') as $entry)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3">{{ $entry->team?->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $entry->seed ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="2">{{ __('No entries yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'matches')
        <div class="space-y-4 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="sm">{{ __('Generated matches') }}</flux:heading>
                <div class="flex items-center gap-2">
                    <flux:checkbox wire:model="forceRegenerate" :label="__('Force regenerate')" />
                    <flux:button variant="primary" wire:click="generateMatches">{{ __('Generate matches') }}</flux:button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Round') }}</th>
                            <th class="px-4 py-3">{{ __('Match') }}</th>
                            <th class="px-4 py-3">{{ __('Field') }}</th>
                            <th class="px-4 py-3">{{ __('Start') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Score') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->tournament->matches->sortBy(['round', 'starts_at', 'id']) as $match)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3">{{ $match->round }}</td>
                                <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Bye') }} - {{ $match->awayTeam?->name ?? __('Bye') }}</td>
                                <td class="px-4 py-3">{{ $match->field?->venue?->name ? $match->field->venue->name.' / '.$match->field->name : '-' }}</td>
                                <td class="px-4 py-3">{{ $match->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $match->status->value)) }}</td>
                                <td class="px-4 py-3">{{ $match->result ? $match->result->home_score.' : '.$match->result->away_score : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="6">{{ __('No matches generated yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'standings')
        <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <flux:heading size="sm">{{ __('Standings') }}</flux:heading>

            <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">{{ __('Team') }}</th>
                            <th class="px-4 py-3">{{ __('P') }}</th>
                            <th class="px-4 py-3">{{ __('W') }}</th>
                            <th class="px-4 py-3">{{ __('D') }}</th>
                            <th class="px-4 py-3">{{ __('L') }}</th>
                            <th class="px-4 py-3">{{ __('GF') }}</th>
                            <th class="px-4 py-3">{{ __('GA') }}</th>
                            <th class="px-4 py-3">{{ __('GD') }}</th>
                            <th class="px-4 py-3">{{ __('Pts') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->standingsRows as $index => $row)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">{{ $row['team_name'] }}</td>
                                <td class="px-4 py-3">{{ $row['played'] }}</td>
                                <td class="px-4 py-3">{{ $row['wins'] }}</td>
                                <td class="px-4 py-3">{{ $row['draws'] }}</td>
                                <td class="px-4 py-3">{{ $row['losses'] }}</td>
                                <td class="px-4 py-3">{{ $row['goals_for'] }}</td>
                                <td class="px-4 py-3">{{ $row['goals_against'] }}</td>
                                <td class="px-4 py-3">{{ $row['goal_difference'] }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $row['points'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="10">{{ __('Standings are not available yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'scores')
        <div class="space-y-4 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Score entry') }}</flux:heading>
                <flux:button :href="route('scores.public', ['organization' => $this->tournament->organization?->slug, 'tournament' => $this->tournament->id])" target="_blank">
                    {{ __('Open public score screen') }}
                </flux:button>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Round') }}</th>
                            <th class="px-4 py-3">{{ __('Match') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Score') }}</th>
                            <th class="px-4 py-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->tournament->matches->sortBy(['round', 'starts_at', 'id']) as $match)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3">{{ $match->round }}</td>
                                <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Bye') }} - {{ $match->awayTeam?->name ?? __('Bye') }}</td>
                                <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $match->status->value)) }}</td>
                                <td class="px-4 py-3">{{ $match->result ? $match->result->home_score.' : '.$match->result->away_score : '-' }}</td>
                                <td class="px-4 py-3">
                                    <flux:button size="sm" :href="route('matches.score', $match)" wire:navigate>{{ __('Enter score') }}</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="5">{{ __('Generate matches first.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
