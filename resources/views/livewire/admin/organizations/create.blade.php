<section class="w-full max-w-2xl">
    <flux:heading>{{ __('Create organization') }}</flux:heading>
    <flux:subheading>{{ __('Add a new tenant organization.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-4">
        <flux:input wire:model="name" :label="__('Name')" required autofocus />
        <flux:input wire:model="slug" :label="__('Slug')" required />
        <flux:input wire:model="billing_email" :label="__('Billing email')" type="email" />

        <div class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model="country" :label="__('Country')" required />
            <flux:input wire:model="timezone" :label="__('Timezone')" required />
            <flux:input wire:model="locale" :label="__('Locale')" required />
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model="selected_plan" :label="__('Selected plan')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->planOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model="subscription_plan" :label="__('Subscription plan')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->planOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model="subscription_status" :label="__('Subscription status')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->statusOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('admin.organizations.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
