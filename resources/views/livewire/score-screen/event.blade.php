<section class="w-full space-y-6">
    <div class="rounded-2xl border border-neutral-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold">{{ $this->event->name }}</h1>
                <p class="mt-1 text-sm text-neutral-600">
                    {{ __('Public event page') }}
                    @if ($this->event->starts_at)
                        <span class="mx-1">•</span>
                        {{ $this->event->starts_at->format('Y-m-d H:i') }}
                    @endif
                    @if ($this->event->ends_at)
                        <span class="mx-1">-</span>
                        {{ $this->event->ends_at->format('Y-m-d H:i') }}
                    @endif
                </p>
            </div>
            <flux:button :href="route('scores.public', ['organization' => $this->event->organization->slug, 'event' => $this->event->id])">{{ __('Back to score screen') }}</flux:button>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-neutral-100 p-3">
                <p class="text-xs uppercase text-neutral-500">{{ __('Tournaments') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ $this->tournaments->count() }}</p>
            </div>
            <div class="rounded-xl bg-neutral-100 p-3">
                <p class="text-xs uppercase text-neutral-500">{{ __('Teams') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ $this->teams->count() }}</p>
            </div>
            <div class="rounded-xl bg-neutral-100 p-3">
                <p class="text-xs uppercase text-neutral-500">{{ __('Scheduled / live') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ $this->schedule->count() }}</p>
            </div>
            <div class="rounded-xl bg-neutral-100 p-3">
                <p class="text-xs uppercase text-neutral-500">{{ __('Results') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ $this->results->count() }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-neutral-200 bg-white p-6">
        <div class="grid gap-3 md:grid-cols-3">
            <flux:select wire:model.live="sport_id" :label="__('Sport')">
                <option value="">{{ __('All sports') }}</option>
                @foreach ($this->sports as $sport)
                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="tournament_id" :label="__('Tournament')">
                <option value="">{{ __('All tournaments') }}</option>
                @foreach ($this->tournaments as $tournament)
                    <option value="{{ $tournament->id }}">{{ $tournament->name }}</option>
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

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="text-xl font-semibold">{{ __('Tournament summary') }}</h2>
            <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Tournament') }}</th>
                            <th class="px-4 py-3">{{ __('Sport') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->tournaments as $tournament)
                            <tr class="border-t border-neutral-200">
                                <td class="px-4 py-3">
                                    <a class="font-medium text-neutral-900 underline" href="{{ route('scores.public.tournament', ['organization' => $this->event->organization->slug, 'tournament' => $tournament->id]) }}">
                                        {{ $tournament->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $tournament->sport?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="2">{{ __('No tournaments with matches found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="text-xl font-semibold">{{ __('Teams') }}</h2>
            <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Name') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->teams as $team)
                            <tr class="border-t border-neutral-200">
                                <td class="px-4 py-3">{{ $team->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500">{{ __('No teams available.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="text-xl font-semibold">{{ __('Schedule') }}</h2>
            <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Time') }}</th>
                            <th class="px-4 py-3">{{ __('Match') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->schedule as $match)
                            <tr class="border-t border-neutral-200">
                                <td class="px-4 py-3">{{ $match->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Home') }} - {{ $match->awayTeam?->name ?? __('Away') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="2">{{ __('No scheduled matches.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-neutral-200 bg-white p-6">
            <h2 class="text-xl font-semibold">{{ __('Results') }}</h2>
            <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3">{{ __('Match') }}</th>
                            <th class="px-4 py-3">{{ __('Score') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->results as $match)
                            <tr class="border-t border-neutral-200">
                                <td class="px-4 py-3">{{ $match->homeTeam?->name ?? __('Home') }} - {{ $match->awayTeam?->name ?? __('Away') }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $match->result?->home_score ?? 0 }} : {{ $match->result?->away_score ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-neutral-500" colspan="2">{{ __('No results yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-neutral-200 bg-white p-6">
        <h2 class="text-xl font-semibold">{{ __('Standings') }}</h2>
        <p class="mt-1 text-sm text-neutral-600">
            {{ $this->standingsTournamentName ? __('Tournament: :name', ['name' => $this->standingsTournamentName]) : __('Select a tournament to view standings.') }}
        </p>
        <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">{{ __('Team') }}</th>
                        <th class="px-3 py-2">{{ __('P') }}</th>
                        <th class="px-3 py-2">{{ __('GD') }}</th>
                        <th class="px-3 py-2">{{ __('Pts') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->standingsRows as $index => $row)
                        <tr class="border-t border-neutral-200">
                            <td class="px-3 py-2">{{ $index + 1 }}</td>
                            <td class="px-3 py-2">{{ $row['team_name'] }}</td>
                            <td class="px-3 py-2">{{ $row['played'] }}</td>
                            <td class="px-3 py-2">{{ $row['goal_difference'] }}</td>
                            <td class="px-3 py-2 font-semibold">{{ $row['points'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-5 text-neutral-500" colspan="5">{{ __('No standings available yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
