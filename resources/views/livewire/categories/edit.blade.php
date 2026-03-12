<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit category') }}</flux:heading>
    <flux:subheading>{{ __('Update category details') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

        <flux:select wire:model="sport_id" :label="__('Sport (optional)')">
            <option value="">{{ __('No sport') }}</option>
            @foreach ($this->sports as $sport)
                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('categories.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
