<section class="w-full">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading>{{ __('Users') }}</flux:heading>
            <flux:subheading>{{ __('Manage non-admin platform users.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" />
            <flux:button :href="route('admin.users.create')" wire:navigate variant="primary">{{ __('Create user') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout class="mt-4" variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Email') }}</th>
                    <th class="px-4 py-3">{{ __('Current org') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->currentOrganizationRelation?->name ?? 'n/a' }}</td>
                        <td class="px-4 py-3">
                            @if ($user->disabled_at)
                                <flux:badge color="red">{{ __('Disabled') }}</flux:badge>
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('admin.users.edit', $user)" wire:navigate>{{ __('Edit') }}</flux:button>
                                @if ($user->disabled_at)
                                    <flux:button size="sm" variant="filled" wire:click="enableUser({{ $user->id }})">{{ __('Enable') }}</flux:button>
                                @else
                                    <flux:button size="sm" variant="danger" wire:click="disableUser({{ $user->id }})">{{ __('Disable') }}</flux:button>
                                @endif
                                <form method="POST" action="{{ route('admin.impersonation.start', $user) }}">
                                    @csrf
                                    <flux:button size="sm" variant="ghost" type="submit">{{ __('Impersonate') }}</flux:button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-zinc-500">{{ __('No users found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</section>
