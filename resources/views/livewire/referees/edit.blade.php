<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit referee') }}</flux:heading>
    <flux:subheading>{{ __('Update referee details') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="first_name" :label="__('First name')" type="text" required autofocus />
            <flux:input wire:model="last_name" :label="__('Last name')" type="text" required />
        </div>

        <flux:select wire:model="sport_id" :label="__('Sport (optional)')">
            <option value="">{{ __('All sports') }}</option>
            @foreach ($this->sports as $sport)
                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model="email" :label="__('Email (optional)')" type="email" />
        <flux:input wire:model="phone" :label="__('Phone (optional)')" type="text" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('referees.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
