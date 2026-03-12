<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Sports') }}</flux:heading>
            <flux:subheading>{{ __('Manage sports and scoring rules') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('sports.create')" wire:navigate>{{ __('Create sport') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:text class="mt-4 font-medium !text-green-600 !dark:text-green-400">{{ session('status') }}</flux:text>
    @endif

    @error('delete')
        <flux:text class="mt-4 font-medium !text-red-600 !dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <div class="mt-6 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Win') }}</th>
                    <th class="px-4 py-3">{{ __('Draw') }}</th>
                    <th class="px-4 py-3">{{ __('Loss') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->sports as $sport)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $sport->name }}</td>
                        <td class="px-4 py-3">{{ $sport->sportRule?->win_points ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $sport->sportRule?->draw_points ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $sport->sportRule?->loss_points ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('sports.edit', $sport)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deleteSport({{ $sport->id }})" wire:confirm="{{ __('Delete this sport?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="5">{{ __('No sports yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
