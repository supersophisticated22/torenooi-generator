<section class="w-full max-w-2xl">
    <flux:heading>{{ __('Edit admin user') }}</flux:heading>
    <flux:subheading>{{ __('Update admin profile and account status.') }}</flux:subheading>

    @if (session('status'))
        <flux:callout class="mt-4" variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @error('is_disabled')
        <flux:callout class="mt-4" variant="danger" icon="x-circle">{{ $message }}</flux:callout>
    @enderror

    @error('demote')
        <flux:callout class="mt-4" variant="danger" icon="x-circle">{{ $message }}</flux:callout>
    @enderror

    <form wire:submit="save" class="mt-6 space-y-4">
        <flux:input wire:model="name" :label="__('Name')" required autofocus />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />

        <label class="flex items-center gap-2 text-sm">
            <input wire:model="is_disabled" type="checkbox" />
            <span>{{ __('Disabled') }}</span>
        </label>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button variant="danger" type="button" wire:click="demote">{{ __('Demote from platform admin') }}</flux:button>
            <flux:button :href="route('admin.admin-users.index')" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </form>
</section>
