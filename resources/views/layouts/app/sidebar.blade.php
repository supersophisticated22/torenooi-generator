<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php($currentOrganizationSlug = auth()->user()->currentOrganization()?->slug)
        @php($isPlatformAdmin = auth()->user()->isPlatformAdmin())
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    @if ($isPlatformAdmin)
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.organizations.index')" :current="request()->routeIs('admin.organizations.*')" wire:navigate>
                            {{ __('Organizations') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="shield-check" :href="route('admin.admin-users.index')" :current="request()->routeIs('admin.admin-users.*')" wire:navigate>
                            {{ __('Admin Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="credit-card" :href="route('admin.subscriptions.index')" :current="request()->routeIs('admin.subscriptions.*')" wire:navigate>
                            {{ __('Subscriptions') }}
                        </flux:sidebar.item>
                    @else
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="trophy" :href="route('events.index')" :current="request()->routeIs('events.*')" wire:navigate>
                            {{ __('Events') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="list-bullet" :href="route('tournaments.index')" :current="request()->routeIs('tournaments.*')" wire:navigate>
                            {{ __('Tournaments') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if (! $isPlatformAdmin)
                    <flux:sidebar.group :heading="__('Participants')" class="grid">
                        <flux:sidebar.item icon="academic-cap" :href="route('sports.index')" :current="request()->routeIs('sports.*')" wire:navigate>
                            {{ __('Sports') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="tag" :href="route('categories.index')" :current="request()->routeIs('categories.*')" wire:navigate>
                            {{ __('Categories') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="shield-check" :href="route('teams.index')" :current="request()->routeIs('teams.*')" wire:navigate>
                            {{ __('Teams') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('players.index')" :current="request()->routeIs('players.*')" wire:navigate>
                            {{ __('Players') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user" :href="route('referees.index')" :current="request()->routeIs('referees.*')" wire:navigate>
                            {{ __('Referees') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group :heading="__('Locations')" class="grid">
                        <flux:sidebar.item icon="building-office-2" :href="route('venues.index')" :current="request()->routeIs('venues.*')" wire:navigate>
                            {{ __('Venues') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="map" :href="route('fields.index')" :current="request()->routeIs('fields.*')" wire:navigate>
                            {{ __('Fields') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group :heading="__('Screens')" class="grid">
                        <flux:sidebar.item icon="tv" :href="$currentOrganizationSlug !== null ? route('scores.public', ['organization' => $currentOrganizationSlug]) : '#'" :current="request()->routeIs('scores.public')">
                            {{ __('Public Score Screen') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
{{--                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">--}}
{{--                    {{ __('Repository') }}--}}
{{--                </flux:sidebar.item>--}}

{{--                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">--}}
{{--                    {{ __('Documentation') }}--}}
{{--                </flux:sidebar.item>--}}
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        @if (session()->has('impersonator_id'))
                            <form method="POST" action="{{ route('admin.impersonation.stop') }}" class="w-full">
                                @csrf
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="arrow-uturn-left"
                                    class="w-full cursor-pointer"
                                >
                                    {{ __('Stop impersonation') }}
                                </flux:menu.item>
                            </form>
                        @endif
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
