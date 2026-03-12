<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-onboarding.progress current="plan" />

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">{{ __('Choose your plan') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Plan and limits are organization-based. You can change later from billing settings.') }}</flux:text>

            @error('plan')
                <flux:callout variant="danger" icon="x-circle" class="mt-4">{{ $message }}</flux:callout>
            @enderror

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($this->plans as $code => $plan)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 {{ $code === 'pro' ? 'ring-2 ring-zinc-900 dark:ring-zinc-100' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <flux:heading>{{ $plan['name'] }}</flux:heading>
                            @if ($code === 'pro')
                                <flux:badge color="emerald">{{ __('Recommended') }}</flux:badge>
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">EUR {{ $plan['price_eur'] }}/month</p>
                        <p class="mt-2 text-xs text-zinc-500">{{ __('Tournaments: :value', ['value' => $plan['limits']['tournaments'] ?? 'Unlimited']) }}</p>
                        <p class="text-xs text-zinc-500">{{ __('Teams: :value', ['value' => $plan['limits']['teams'] ?? 'Unlimited']) }}</p>

                        <flux:button wire:click="selectPlan('{{ $code }}')" variant="primary" class="mt-4 w-full">
                            {{ __('Select :plan', ['plan' => $plan['name']]) }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
