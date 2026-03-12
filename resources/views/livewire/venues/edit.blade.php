<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit venue') }}</flux:heading>
    <flux:subheading>{{ __('Update venue details') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="address" :label="__('Address (optional)')" type="text" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('venues.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
