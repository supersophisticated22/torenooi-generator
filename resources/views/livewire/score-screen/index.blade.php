<section class="w-full space-y-6">
    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ __('Public Score Screen') }}</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">{{ __('Live matches, results, and standings') }}</p>
            </div>
            <p class="text-sm font-medium text-neutral-600 dark:text-neutral-300">{{ now()->format('Y-m-d H:i') }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-5">
            <flux:select wire:model.live="event_id" :label="__('Event')">
                <option value="">{{ __('All events') }}</option>
                @foreach ($this->events as $event)
                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="tournament_id" :label="__('Tournament')">
                <option value="">{{ __('All tournaments') }}</option>
                @foreach ($this->tournaments as $tournament)
                    <option value="{{ $tournament->id }}">{{ $tournament->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="sport_id" :label="__('Sport')">
                <option value="">{{ __('All sports') }}</option>
                @foreach ($this->sports as $sport)
                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="venue_id" :label="__('Venue')">
                <option value="">{{ __('All venues') }}</option>
                @foreach ($this->venues as $venue)
                    <option value="{{ $venue->id }}">{{ $venue->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="field_id" :label="__('Field')">
                <option value="">{{ __('All fields') }}</option>
                @foreach ($this->fields as $field)
                    <option value="{{ $field->id }}">{{ $field->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
                <h2 class="text-xl font-semibold">{{ __('Current match') }}</h2>

                @if ($this->currentMatch)
                    <div class="mt-4 grid gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/40 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <p class="text-2xl font-semibold">
                                {{ $this->currentMatch->homeTeam?->name ?? __('Home') }}
                                <span class="mx-2 text-neutral-500">{{ __('vs') }}</span>
                                {{ $this->currentMatch->awayTeam?->name ?? __('Away') }}
                            </p>
                            <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $this->currentMatch->tournament?->name ?? '-' }}
                                @if ($this->currentMatch->field)
                                    <span class="mx-1">•</span>
                                    {{ $this->currentMatch->field->venue?->name ?? '-' }} / {{ $this->currentMatch->field->name }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Start') }}</p>
                            <p class="text-lg font-medium">{{ $this->currentMatch->starts_at?->format('H:i') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Status') }}</p>
                            <p class="text-lg font-medium">{{ ucfirst(str_replace('_', ' ', $this->currentMatch->status->value)) }}</p>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-neutral-500">{{ __('No current match in this filter set.') }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
                <h2 class="text-xl font-semibold">{{ __('Upcoming matches') }}</h2>

                <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                            <tr>
                                <th class="px-4 py-3">{{ __('Time') }}</th>
                                <th class="px-4 py-3">{{ __('Match') }}</th>
                                <th class="px-4 py-3">{{ __('Tournament') }}</th>
                                <th class="px-4 py-3">{{ __('Field') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->upcomingMatches as $match)
                                <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                    <td class="px-4 py-3">{{ $match->starts_at?->format('H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Home') }} - {{ $match->awayTeam?->name ?? __('Away') }}</td>
                                    <td class="px-4 py-3">{{ $match->tournament?->name ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $match->field?->venue?->name ? $match->field->venue->name.' / '.$match->field->name : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-neutral-500" colspan="4">{{ __('No upcoming matches.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
                <h2 class="text-xl font-semibold">{{ __('Latest results') }}</h2>

                <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                            <tr>
                                <th class="px-4 py-3">{{ __('Finished') }}</th>
                                <th class="px-4 py-3">{{ __('Match') }}</th>
                                <th class="px-4 py-3">{{ __('Score') }}</th>
                                <th class="px-4 py-3">{{ __('Tournament') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->latestResults as $match)
                                <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                    <td class="px-4 py-3">{{ $match->ends_at?->format('H:i') ?? $match->updated_at->format('H:i') }}</td>
                                    <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Home') }} - {{ $match->awayTeam?->name ?? __('Away') }}</td>
                                    <td class="px-4 py-3 text-lg font-semibold">{{ $match->result?->home_score ?? 0 }} : {{ $match->result?->away_score ?? 0 }}</td>
                                    <td class="px-4 py-3">{{ $match->tournament?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-neutral-500" colspan="4">{{ __('No completed matches yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="xl:col-span-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
                <h2 class="text-xl font-semibold">{{ __('Standings') }}</h2>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                    {{ $this->standingsTournamentName ? __('Tournament: :name', ['name' => $this->standingsTournamentName]) : __('Select a tournament to view standings.') }}
                </p>

                <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                            <tr>
                                <th class="px-3 py-2">#</th>
                                <th class="px-3 py-2">{{ __('Team') }}</th>
                                <th class="px-3 py-2">{{ __('P') }}</th>
                                <th class="px-3 py-2">{{ __('W') }}</th>
                                <th class="px-3 py-2">{{ __('D') }}</th>
                                <th class="px-3 py-2">{{ __('L') }}</th>
                                <th class="px-3 py-2">{{ __('GD') }}</th>
                                <th class="px-3 py-2">{{ __('Pts') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->standingsRows as $index => $row)
                                <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                    <td class="px-3 py-2 font-medium">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2">{{ $row['team_name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['played'] }}</td>
                                    <td class="px-3 py-2">{{ $row['wins'] }}</td>
                                    <td class="px-3 py-2">{{ $row['draws'] }}</td>
                                    <td class="px-3 py-2">{{ $row['losses'] }}</td>
                                    <td class="px-3 py-2">{{ $row['goal_difference'] }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $row['points'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-5 text-neutral-500" colspan="8">{{ __('No standings available yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
