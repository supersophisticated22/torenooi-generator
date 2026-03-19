<section class="w-full">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading>{{ __('Organizations') }}</flux:heading>
            <flux:subheading>{{ __('Manage all organizations across the platform.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" />
            <flux:button :href="route('admin.organizations.create')" wire:navigate variant="primary">{{ __('Create organization') }}</flux:button>
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
                    <th class="px-4 py-3">{{ __('Slug') }}</th>
                    <th class="px-4 py-3">{{ __('Billing') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="px-4 py-3">{{ $organization->name }}</td>
                        <td class="px-4 py-3">{{ $organization->slug }}</td>
                        <td class="px-4 py-3">{{ $organization->billing_email ?? 'n/a' }}</td>
                        <td class="px-4 py-3">
                            @if ($organization->disabled_at)
                                <flux:badge color="red">{{ __('Disabled') }}</flux:badge>
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('admin.organizations.edit', $organization)" wire:navigate>{{ __('Edit') }}</flux:button>
                                @if ($organization->disabled_at)
                                    <flux:button size="sm" variant="filled" wire:click="enableOrganization({{ $organization->id }})">{{ __('Enable') }}</flux:button>
                                @else
                                    <flux:button size="sm" variant="danger" wire:click="disableOrganization({{ $organization->id }})">{{ __('Disable') }}</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-zinc-500">{{ __('No organizations found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $organizations->links() }}</div>
</section>
