<section class="w-full">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading>{{ __('Subscriptions') }}</flux:heading>
            <flux:subheading>{{ __('Manage organization subscriptions and billing snapshots.') }}</flux:subheading>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search organizations')" />
    </div>

    @if (session('status'))
        <flux:callout class="mt-4" variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div class="mt-6 space-y-4">
        @forelse ($organizations as $organization)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ $organization->name }} (#{{ $organization->id }})</flux:heading>
                    <span class="text-xs text-zinc-500">{{ __('Latest subscription: :id', ['id' => $latestSubscriptions->get($organization->id)?->id ?? 'n/a']) }}</span>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.stripe_subscription_id" :label="__('Stripe subscription ID')" />
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.stripe_price_id" :label="__('Stripe price ID')" />
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.quantity" :label="__('Quantity')" type="number" />
                    <flux:select wire:model="subscriptionForms.{{ $organization->id }}.plan_code" :label="__('Plan')">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($this->planOptions() as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="subscriptionForms.{{ $organization->id }}.status" :label="__('Status')">
                        @foreach ($this->subscriptionStatusOptions() as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.trial_ends_at" :label="__('Trial ends at')" type="datetime-local" />
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.current_period_start" :label="__('Current period start')" type="datetime-local" />
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.current_period_end" :label="__('Current period end')" type="datetime-local" />
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.cancel_at" :label="__('Cancel at')" type="datetime-local" />
                    <flux:input wire:model="subscriptionForms.{{ $organization->id }}.canceled_at" :label="__('Canceled at')" type="datetime-local" />
                </div>

                <div class="mt-3">
                    <flux:textarea wire:model="subscriptionForms.{{ $organization->id }}.metadata_json" :label="__('Metadata JSON')" rows="3" />
                </div>

                <div class="mt-3">
                    <flux:button variant="primary" size="sm" wire:click="saveSubscription({{ $organization->id }})">{{ __('Save subscription') }}</flux:button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">{{ __('No organizations found.') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $organizations->links() }}</div>
</section>
