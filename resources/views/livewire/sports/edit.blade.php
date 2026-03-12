<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit sport') }}</flux:heading>
    <flux:subheading>{{ __('Update sport details and scoring rules') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="win_points" :label="__('Win points')" type="number" min="0" required />
            <flux:input wire:model="draw_points" :label="__('Draw points')" type="number" min="0" required />
            <flux:input wire:model="loss_points" :label="__('Loss points')" type="number" min="0" required />
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('sports.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
