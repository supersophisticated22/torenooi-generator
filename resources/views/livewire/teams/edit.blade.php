<section class="w-full max-w-xl">
    <flux:heading>{{ __('Edit team') }}</flux:heading>
    <flux:subheading>{{ __('Update team details') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="short_name" :label="__('Short name')" type="text" />

        <flux:select wire:model="sport_id" :label="__('Sport')" required>
            <option value="">{{ __('Select sport') }}</option>
            @foreach ($this->sports as $sport)
                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model="category_id" :label="__('Category (optional)')">
            <option value="">{{ __('No category') }}</option>
            @foreach ($this->categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('teams.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
