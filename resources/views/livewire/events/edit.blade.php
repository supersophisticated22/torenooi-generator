<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit event') }}</flux:heading>
    <flux:subheading>{{ __('Update event details') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="starts_at" :label="__('Start datetime')" type="datetime-local" />
        <flux:input wire:model="ends_at" :label="__('End datetime')" type="datetime-local" />

        <flux:select wire:model="status" :label="__('Status')" required>
            @foreach ($this->statusOptions() as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </flux:select>
        <flux:checkbox wire:model="is_private" :label="__('Private event (hide on public score screens)')" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('events.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
