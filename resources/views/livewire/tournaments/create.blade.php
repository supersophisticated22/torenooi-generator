<section class="w-full max-w-2xl">
    <flux:heading>{{ __('Create tournament') }}</flux:heading>
    <flux:subheading>{{ __('Configure tournament settings') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="event_id" :label="__('Event')" required>
                <option value="">{{ __('Select event') }}</option>
                @foreach ($this->events as $event)
                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="sport_id" :label="__('Sport')" required>
                <option value="">{{ __('Select sport') }}</option>
                @foreach ($this->sports as $sport)
                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:select wire:model="category_id" :label="__('Category (optional)')">
            <option value="">{{ __('No category') }}</option>
            @foreach ($this->categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </flux:select>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model="type" :label="__('Type')" required>
                @foreach ($this->tournamentTypeOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="final_type" :label="__('Final type')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->tournamentFinalTypeOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="status" :label="__('Status')" required>
                @foreach ($this->tournamentStatusOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <flux:input wire:model="pool_count" :label="__('Pool count')" type="number" min="0" required />
            <flux:input wire:model="match_duration_minutes" :label="__('Match length')" type="number" min="1" />
            <flux:input wire:model="break_duration_minutes" :label="__('Break')" type="number" min="0" />
            <flux:input wire:model="final_break_minutes" :label="__('Final break')" type="number" min="0" />
        </div>

        <flux:input wire:model="scheduled_start_at" :label="__('Start datetime')" type="datetime-local" />

        <div class="space-y-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="sm">{{ __('Tournament card popup settings') }}</flux:heading>
            <flux:checkbox wire:model="card_popup_enabled" :label="__('Enable card popup')" />

            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($this->cardEventTypeOptions() as $option)
                    <flux:checkbox wire:model="card_popup_types" value="{{ $option['value'] }}" :label="__($option['label'])" />
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="card_popup_condition" :label="__('Display condition')">
                    @foreach ($this->cardPopupConditionOptions() as $option)
                        <option value="{{ $option['value'] }}">{{ __($option['label']) }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="card_popup_threshold" :label="__('Threshold')" type="number" min="1" max="100" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('tournaments.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
