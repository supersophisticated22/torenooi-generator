<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Billing')" :subheading="__('Manage your plan, payment methods, and invoices.')">
        <div class="space-y-6">
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle">
                    {{ session('status') }}
                </flux:callout>
            @endif

            @error('billing')
                <flux:callout variant="danger" icon="x-circle">
                    {{ $message }}
                </flux:callout>
            @enderror

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Current subscription') }}</flux:heading>
                <div class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                    <p>{{ __('Plan') }}: <strong>{{ strtoupper($this->organization->activePlan()->value) }}</strong></p>
                    <p>{{ __('Status') }}: <strong>{{ $this->organization->subscription_status?->value ?? 'none' }}</strong></p>
                    <p>{{ __('Trial ends at') }}: <strong>{{ $this->organization->trial_ends_at?->toDateString() ?? 'n/a' }}</strong></p>
                </div>

                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('billing.portal') }}">
                        @csrf
                        <flux:button type="submit" variant="filled">{{ __('Open billing portal') }}</flux:button>
                    </form>

                    @if ($this->currentSubscription && $this->currentSubscription->cancel_at === null)
                        <flux:button wire:click="cancelSubscription" variant="danger">{{ __('Cancel at period end') }}</flux:button>
                    @endif

                    @if ($this->currentSubscription && $this->currentSubscription->cancel_at !== null)
                        <flux:button wire:click="resumeSubscription" variant="primary">{{ __('Resume subscription') }}</flux:button>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Plans') }}</flux:heading>

                @foreach ($this->plans as $code => $plan)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:heading>{{ $plan['name'] }}</flux:heading>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">€{{ $plan['price_eur'] }}/month</p>
                            </div>

                            <form method="POST" action="{{ route('billing.checkout', ['plan' => $code]) }}">
                                @csrf
                                <flux:button type="submit" variant="primary">{{ __('Choose :plan', ['plan' => $plan['name']]) }}</flux:button>
                            </form>
                        </div>

                        <div class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <p>{{ __('Tournament limit') }}: {{ $plan['limits']['tournaments'] ?? 'Unlimited' }}</p>
                            <p>{{ __('Team limit') }}: {{ $plan['limits']['teams'] ?? 'Unlimited' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-settings.layout>
</section>
