<section class="w-full space-y-5">
    <div class="rounded-2xl border border-neutral-200 bg-white p-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold md:text-3xl">{{ __('Public Score Screen') }}</h1>
                <p class="mt-1 text-sm text-neutral-600">{{ __('Live matches, results, and standings') }}</p>
            </div>
            <p class="text-sm font-medium text-neutral-600">{{ now()->format('Y-m-d H:i') }}</p>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-5">
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

    <div class="rounded-2xl border border-neutral-200 bg-white p-5">
        <h2 class="text-lg font-semibold md:text-xl">{{ __('Current match') }}</h2>
        @if ($this->currentMatch)
            <div class="mt-4 rounded-xl bg-neutral-100 p-4 md:p-6">
                <div class="grid items-center gap-4 lg:grid-cols-3">
                    <div class="text-center lg:text-left">
                        <p class="text-lg font-semibold md:text-2xl">{{ $this->currentMatch->homeTeam?->name ?? __('Home') }}</p>
                        <p class="mt-1 text-sm text-neutral-600">{{ __('Home') }}</p>
                    </div>

                    <div class="text-center">
                        <p class="text-5xl font-bold tracking-tight md:text-7xl">
                            {{ $this->currentMatch->result?->home_score ?? 0 }}
                            <span class="mx-2">:</span>
                            {{ $this->currentMatch->result?->away_score ?? 0 }}
                        </p>
                        <p class="mt-2 text-xs uppercase tracking-wide text-neutral-600">{{ ucfirst(str_replace('_', ' ', $this->currentMatch->status->value)) }}</p>
                    </div>

                    <div class="text-center lg:text-right">
                        <p class="text-lg font-semibold md:text-2xl">{{ $this->currentMatch->awayTeam?->name ?? __('Away') }}</p>
                        <p class="mt-1 text-sm text-neutral-600">{{ __('Away') }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-sm text-neutral-600 lg:justify-between">
                    <span>
                        {{ __('Tournament:') }}
                        <a class="font-medium underline" href="{{ route('scores.public.tournament', ['organization' => $this->currentMatch->tournament->organization->slug, 'tournament' => $this->currentMatch->tournament->id]) }}">
                            {{ $this->currentMatch->tournament?->name ?? '-' }}
                        </a>
                    </span>
                    <span>
                        {{ __('Field:') }}
                        {{ $this->currentMatch->field?->venue?->name ? $this->currentMatch->field->venue->name.' / '.$this->currentMatch->field->name : '-' }}
                    </span>
                    <span>{{ __('Start: :time', ['time' => $this->currentMatch->starts_at?->format('H:i') ?? '-']) }}</span>
                </div>
            </div>
        @else
            <p class="mt-4 text-neutral-500">{{ __('No current match in this filter set.') }}</p>
        @endif
    </div>

    <div class="grid gap-5 xl:grid-cols-12">
        <div class="space-y-5 xl:col-span-8">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-lg font-semibold md:text-xl">{{ __('Upcoming matches') }}</h2>
                <div class="mt-3 overflow-hidden rounded-xl border border-neutral-200">
                    <table class="w-full text-left text-sm md:text-base">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3">{{ __('Time') }}</th>
                                <th class="px-4 py-3">{{ __('Match') }}</th>
                                <th class="px-4 py-3">{{ __('Tournament') }}</th>
                                <th class="px-4 py-3">{{ __('Field') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->upcomingMatches as $match)
                                <tr class="border-t border-neutral-200">
                                    <td class="px-4 py-3">{{ $match->starts_at?->format('H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Home') }} - {{ $match->awayTeam?->name ?? __('Away') }}</td>
                                    <td class="px-4 py-3">
                                        <a class="underline" href="{{ route('scores.public.tournament', ['organization' => $match->tournament->organization->slug, 'tournament' => $match->tournament->id]) }}">
                                            {{ $match->tournament?->name ?? '-' }}
                                        </a>
                                    </td>
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

            <div class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-lg font-semibold md:text-xl">{{ __('Latest results') }}</h2>
                <div class="mt-3 overflow-hidden rounded-xl border border-neutral-200">
                    <table class="w-full text-left text-sm md:text-base">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3">{{ __('Finished') }}</th>
                                <th class="px-4 py-3">{{ __('Match') }}</th>
                                <th class="px-4 py-3">{{ __('Score') }}</th>
                                <th class="px-4 py-3">{{ __('Tournament') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->latestResults as $match)
                                <tr class="border-t border-neutral-200">
                                    <td class="px-4 py-3">{{ $match->ends_at?->format('H:i') ?? $match->updated_at->format('H:i') }}</td>
                                    <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Home') }} - {{ $match->awayTeam?->name ?? __('Away') }}</td>
                                    <td class="px-4 py-3 text-lg font-semibold md:text-2xl">{{ $match->result?->home_score ?? 0 }} : {{ $match->result?->away_score ?? 0 }}</td>
                                    <td class="px-4 py-3">
                                        <a class="underline" href="{{ route('scores.public.tournament', ['organization' => $match->tournament->organization->slug, 'tournament' => $match->tournament->id]) }}">
                                            {{ $match->tournament?->name ?? '-' }}
                                        </a>
                                    </td>
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
            <div class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-lg font-semibold md:text-xl">{{ __('Standings') }}</h2>
                <p class="mt-1 text-sm text-neutral-600">
                    {{ $this->standingsTournamentName ? __('Tournament: :name', ['name' => $this->standingsTournamentName]) : __('Select a tournament to view standings.') }}
                </p>
                @if ($this->standingsTournamentName && $this->tournament_id === null && $this->latestResults->first()?->tournament)
                    <p class="mt-2 text-xs text-neutral-500">
                        <a class="underline" href="{{ route('scores.public.tournament', ['organization' => $this->latestResults->first()->tournament->organization->slug, 'tournament' => $this->latestResults->first()->tournament->id]) }}">
                            {{ __('Open tournament page') }}
                        </a>
                    </p>
                @endif

                <div class="mt-3 overflow-hidden rounded-xl border border-neutral-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-neutral-50">
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
                                <tr class="border-t border-neutral-200">
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
