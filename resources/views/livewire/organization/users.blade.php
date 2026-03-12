<section class="w-full">
    @include('partials.settings-heading')

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @error('remove')
        <flux:callout variant="danger" icon="x-circle">{{ $message }}</flux:callout>
    @enderror

    <x-settings.layout
        :heading="__('Organization users')"
        :subheading="__('Manage who can log in and access this organization.')"
        contentMaxWidth="max-w-5xl"
    >
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading>{{ __('Add or invite user') }}</flux:heading>

                <form wire:submit="saveUser" class="mt-4 grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name (required for new user)')" />
                    <flux:input wire:model="email" type="email" :label="__('Email')" required />
                    <flux:input wire:model="password" type="password" :label="__('Password (required for new user)')" viewable />
                    <flux:select wire:model="role" :label="__('Role')">
                        @foreach ($this->roleOptions() as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </flux:select>

                    <div class="md:col-span-2">
                        <flux:button type="submit" variant="primary">{{ __('Save user access') }}</flux:button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading>{{ __('Members') }}</flux:heading>

                <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3">{{ __('Role') }}</th>
                                <th class="px-4 py-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->members as $member)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-4 py-3">{{ $member->name }}</td>
                                    <td class="px-4 py-3">{{ $member->email }}</td>
                                    <td class="px-4 py-3">
                                        <select
                                            class="rounded-md border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-600 dark:bg-zinc-900"
                                            wire:change="updateRole({{ $member->id }}, $event.target.value)"
                                        >
                                            @foreach ($this->roleOptions() as $option)
                                                <option value="{{ $option['value'] }}" @selected($member->pivot->role === $option['value'])>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:button wire:click="removeUser({{ $member->id }})" size="xs" variant="danger">{{ __('Remove') }}</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-zinc-500" colspan="4">{{ __('No members yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
