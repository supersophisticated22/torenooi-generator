<section class="w-full max-w-2xl">
    <flux:heading>{{ __('Create admin user') }}</flux:heading>
    <flux:subheading>{{ __('Create a new platform admin safely from this dedicated area.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-4">
        <flux:input wire:model="name" :label="__('Name')" required autofocus />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="password" :label="__('Password')" type="password" required viewable />

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="organization_id" :label="__('Organization (optional)')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->organizations() as $organization)
                    <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model="role" :label="__('Organization role')">
                @foreach ($this->roleOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('admin.admin-users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
