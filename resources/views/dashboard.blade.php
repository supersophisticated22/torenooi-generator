<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">{{ $organization->name }}</flux:heading>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('Plan: :plan', ['plan' => strtoupper($organization->activePlan()->value)]) }}
                <span class="mx-2">•</span>
                {{ __('Subscription: :status', ['status' => $organization->subscription_status?->value ?? 'none']) }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">{{ __('Sports') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $sportsCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">{{ __('Teams') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $teamsCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">{{ __('Events') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $eventsCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">{{ __('Tournaments') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $tournamentsCount }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">{{ __('Upcoming matches') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $upcomingMatchesCount }}</p>
            </div>
        </div>

        @if ($sportsCount === 0 || $teamsCount === 0 || $eventsCount === 0 || $tournamentsCount === 0)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading>{{ __('Get started') }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Set up the essentials to run your first tournament.') }}</p>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @if ($sportsCount === 0)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-medium">{{ __('No sports yet') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Create your first sport to define tournament rules.') }}</p>
                            <a href="{{ route('sports.create') }}" class="mt-3 inline-block">
                                <flux:button size="sm" variant="filled">{{ __('Create sport') }}</flux:button>
                            </a>
                        </div>
                    @endif

                    @if ($teamsCount === 0)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-medium">{{ __('No teams yet') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Add teams that can join your tournaments.') }}</p>
                            <a href="{{ route('teams.create') }}" class="mt-3 inline-block">
                                <flux:button size="sm" variant="filled">{{ __('Create team') }}</flux:button>
                            </a>
                        </div>
                    @endif

                    @if ($eventsCount === 0)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-medium">{{ __('No events yet') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Create an event to group your tournaments.') }}</p>
                            <a href="{{ route('events.create') }}" class="mt-3 inline-block">
                                <flux:button size="sm" variant="filled">{{ __('Create event') }}</flux:button>
                            </a>
                        </div>
                    @endif

                    @if ($tournamentsCount === 0)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-medium">{{ __('No tournaments yet') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Set up your first tournament bracket and schedule.') }}</p>
                            <a href="{{ route('tournaments.create') }}" class="mt-3 inline-block">
                                <flux:button size="sm" variant="filled">{{ __('Create tournament') }}</flux:button>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading>{{ __('Quick actions') }}</flux:heading>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <a href="{{ route('sports.create') }}"><flux:button variant="filled" class="w-full">{{ __('Create sport') }}</flux:button></a>
                <a href="{{ route('teams.create') }}"><flux:button variant="filled" class="w-full">{{ __('Create team') }}</flux:button></a>
                <a href="{{ route('venues.create') }}"><flux:button variant="filled" class="w-full">{{ __('Create venue') }}</flux:button></a>
                <a href="{{ route('events.create') }}"><flux:button variant="filled" class="w-full">{{ __('Create event') }}</flux:button></a>
                <a href="{{ route('tournaments.create') }}"><flux:button variant="filled" class="w-full">{{ __('Create tournament') }}</flux:button></a>
            </div>
        </div>
    </div>
</x-layouts::app>
