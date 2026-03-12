<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit player') }}</flux:heading>
    <flux:subheading>{{ __('Update player details') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="first_name" :label="__('First name')" type="text" required autofocus />
        <flux:input wire:model="last_name" :label="__('Last name')" type="text" required />
        <flux:input wire:model="email" :label="__('Email (optional)')" type="email" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('players.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
