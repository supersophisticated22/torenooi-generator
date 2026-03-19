<section class="w-full">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading>{{ __('Admin Users') }}</flux:heading>
            <flux:subheading>{{ __('Manage platform admin accounts in a dedicated section.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" />
            <flux:button :href="route('admin.admin-users.create')" wire:navigate variant="primary">{{ __('Create admin user') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout class="mt-4" variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @error('disable')
        <flux:callout class="mt-4" variant="danger" icon="x-circle">{{ $message }}</flux:callout>
    @enderror

    @error('demote')
        <flux:callout class="mt-4" variant="danger" icon="x-circle">{{ $message }}</flux:callout>
    @enderror

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Email') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="px-4 py-3">{{ $admin->name }}</td>
                        <td class="px-4 py-3">{{ $admin->email }}</td>
                        <td class="px-4 py-3">
                            @if ($admin->disabled_at)
                                <flux:badge color="red">{{ __('Disabled') }}</flux:badge>
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('admin.admin-users.edit', $admin)" wire:navigate>{{ __('Edit') }}</flux:button>
                                @if ($admin->disabled_at)
                                    <flux:button size="sm" variant="filled" wire:click="enableAdmin({{ $admin->id }})">{{ __('Enable') }}</flux:button>
                                @else
                                    <flux:button size="sm" variant="danger" wire:click="disableAdmin({{ $admin->id }})">{{ __('Disable') }}</flux:button>
                                @endif
                                <flux:button size="sm" variant="ghost" wire:click="demoteAdmin({{ $admin->id }})">{{ __('Demote') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-zinc-500">{{ __('No admin users found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $admins->links() }}</div>
</section>
