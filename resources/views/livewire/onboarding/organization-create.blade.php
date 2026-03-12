<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <x-onboarding.progress current="organization" />

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="xl">{{ __('Create your organization') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Set up your club, school, or organizer profile to start managing tournaments.') }}</flux:text>

            <form wire:submit="save" class="mt-6 space-y-4">
                <flux:input wire:model="name" :label="__('Organization name')" required />
                <flux:input wire:model="slug" :label="__('Public slug')" description="Used in public URLs" required />
                <flux:input wire:model="billing_email" type="email" :label="__('Billing email')" required />

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select wire:model="country" :label="__('Country')">
                        <option value="NL">Netherlands</option>
                        <option value="BE">Belgium</option>
                    </flux:select>
                    <flux:select wire:model="locale" :label="__('Locale')">
                        <option value="nl">Nederlands</option>
                        <option value="en">English</option>
                        <option value="fr">Francais</option>
                    </flux:select>
                </div>

                <flux:input wire:model="timezone" :label="__('Timezone')" required />
                <flux:input wire:model="primary_color" :label="__('Primary color (optional)')" placeholder="#0f766e" />

                <div class="pt-2">
                    <flux:button type="submit" variant="primary">{{ __('Create organization') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
