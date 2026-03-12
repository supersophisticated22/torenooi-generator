<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-onboarding.progress current="payment" />

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">{{ __('Complete your subscription') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Your organization will be ready after Stripe confirms the subscription.') }}</flux:text>

            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" class="mt-4">{{ session('status') }}</flux:callout>
            @endif

            @if (session('warning'))
                <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">{{ session('warning') }}</flux:callout>
            @endif

            @if ($this->organizationModel?->subscription_status)
                <p class="mt-4 text-sm">
                    {{ __('Current subscription status: :status', ['status' => $this->organizationModel->subscription_status->value]) }}
                </p>
            @endif

            @if ($this->selectedPlanMetadata)
                <div class="mt-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading>{{ $this->selectedPlanMetadata['name'] }}</flux:heading>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">EUR {{ $this->selectedPlanMetadata['price_eur'] }}/month</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($this->isAdmin)
                    <form method="POST" action="{{ route('onboarding.checkout.start') }}">
                        @csrf
                        <flux:button type="submit" variant="primary">{{ __('Continue to Stripe Checkout') }}</flux:button>
                    </form>
                @else
                    <flux:callout variant="warning" icon="information-circle">{{ __('An organization admin must complete billing.') }}</flux:callout>
                @endif

                @if ($this->organizationModel?->subscription_status && in_array($this->organizationModel->subscription_status->value, ['active', 'trialing'], true))
                    <a href="{{ route('dashboard') }}">
                        <flux:button variant="filled">{{ __('Go to dashboard') }}</flux:button>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
