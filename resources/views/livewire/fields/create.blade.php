<section class="w-full max-w-xl">
    <flux:heading>{{ __('Create field') }}</flux:heading>
    <flux:subheading>{{ __('Add a field to a venue') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="code" :label="__('Code (optional)')" type="text" />

        <flux:select wire:model="venue_id" :label="__('Venue')" required>
            <option value="">{{ __('Select venue') }}</option>
            @foreach ($this->venues as $venue)
                <option value="{{ $venue->id }}">{{ $venue->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model="sport_id" :label="__('Sport (optional)')">
            <option value="">{{ __('No sport restriction') }}</option>
            @foreach ($this->sports as $sport)
                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('fields.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
