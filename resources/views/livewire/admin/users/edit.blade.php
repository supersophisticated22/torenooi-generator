<section class="w-full max-w-3xl">
    <flux:heading>{{ __('Edit user') }}</flux:heading>
    <flux:subheading>{{ __('Update profile, memberships, and account status.') }}</flux:subheading>

    @if (session('status'))
        <flux:callout class="mt-4" variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="mt-6 space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            <flux:input wire:model="email" :label="__('Email')" type="email" required />
            <flux:select wire:model="current_organization_id" :label="__('Current organization')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->organizations() as $organization)
                    <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                @endforeach
            </flux:select>
            <label class="flex items-center gap-2 text-sm mt-7">
                <input wire:model="is_disabled" type="checkbox" />
                <span>{{ __('Disabled') }}</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save user') }}</flux:button>
            <flux:button :href="route('admin.users.index')" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </form>

    <div class="mt-8 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading>{{ __('Memberships') }}</flux:heading>

        <div class="mt-4 space-y-3">
            @forelse ($this->memberships() as $membership)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium">{{ $membership->name }}</span>
                    <flux:select wire:model="membershipRoles.{{ $membership->id }}" wire:change="saveMembershipRole({{ $membership->id }}, $event.target.value)">
                        @foreach ($this->roleOptions() as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </flux:select>
                    <flux:button size="xs" variant="danger" wire:click="removeMembership({{ $membership->id }})">{{ __('Remove') }}</flux:button>
                </div>
            @empty
                <p class="text-sm text-zinc-500">{{ __('No memberships yet.') }}</p>
            @endforelse
        </div>

        <form wire:submit="addMembership" class="mt-4 grid gap-3 md:grid-cols-3">
            <flux:select wire:model="new_organization_id" :label="__('Organization')">
                <option value="">{{ __('Choose organization') }}</option>
                @foreach ($this->organizations() as $organization)
                    <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model="new_role" :label="__('Role')">
                @foreach ($this->roleOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>
            <div class="flex items-end">
                <flux:button variant="filled" type="submit">{{ __('Add membership') }}</flux:button>
            </div>
        </form>
    </div>
</section>
