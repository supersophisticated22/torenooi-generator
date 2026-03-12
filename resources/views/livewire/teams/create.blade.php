<section class="w-full max-w-xl">
    <flux:heading>{{ __('Create team') }}</flux:heading>
    <flux:subheading>{{ __('Add a team to your organization') }}</flux:subheading>

    @error('plan')
        <flux:callout variant="danger" icon="x-circle" class="mt-4">
            <div class="space-y-2">
                <p>{{ $message }}</p>

                @can('manage-organization-billing')
                    <flux:button size="sm" :href="route('billing.show')" wire:navigate>{{ __('Upgrade plan') }}</flux:button>
                @else
                    <p class="text-sm">{{ __('Please contact your organization admin to upgrade the plan.') }}</p>
                @endcan
            </div>
        </flux:callout>
    @enderror

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
