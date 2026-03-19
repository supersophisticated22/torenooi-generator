<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ __('Players') }}</flux:heading>
            <flux:subheading>{{ __('Manage individual players') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:modal.trigger name="import-players-modal">
                <flux:button x-data="" x-on:click.prevent="$dispatch('open-modal', 'import-players-modal')">{{ __('Import players') }}</flux:button>
            </flux:modal.trigger>

            <flux:button variant="primary" :href="route('players.create')" wire:navigate>{{ __('Create player') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:text class="mt-4 font-medium !text-green-600 !dark:text-green-400">{{ session('status') }}</flux:text>
    @endif

    @error('delete')
        <flux:text class="mt-4 font-medium !text-red-600 !dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <flux:modal name="import-players-modal" class="max-w-4xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Import players') }}</flux:heading>
                <flux:subheading>{{ __('Upload a CSV or Excel file, map columns, and import into a team.') }}</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="import_team_id" :label="__('Team')" required>
                    <option value="">{{ __('Select team') }}</option>
                    @foreach ($this->importTeams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="import_file" :label="__('File')" type="file" accept=".csv,.xlsx" required />
            </div>

            <div class="flex items-center gap-2">
                <flux:button wire:click="prepareImport">{{ __('Analyze file') }}</flux:button>
                <flux:button wire:click="applySuggestedMapping">{{ __('Auto map fields') }}</flux:button>
            </div>

            @if ($import_status)
                <flux:text class="font-medium !text-green-600 !dark:text-green-400">{{ $import_status }}</flux:text>
            @endif

            @if ($import_headers !== [])
                <div class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:heading size="sm">{{ __('Field mapping') }}</flux:heading>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:select wire:model="import_mapping.first_name" :label="__('First name column')" required>
                            <option value="">{{ __('Select column') }}</option>
                            @foreach ($import_headers as $columnIndex => $header)
                                <option value="{{ $columnIndex }}">{{ $header }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="import_mapping.last_name" :label="__('Last name column')" required>
                            <option value="">{{ __('Select column') }}</option>
                            @foreach ($import_headers as $columnIndex => $header)
                                <option value="{{ $columnIndex }}">{{ $header }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="import_mapping.number" :label="__('Number column')" required>
                            <option value="">{{ __('Select column') }}</option>
                            @foreach ($import_headers as $columnIndex => $header)
                                <option value="{{ $columnIndex }}">{{ $header }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="import_mapping.email" :label="__('Email column (optional)')">
                            <option value="">{{ __('Not mapped') }}</option>
                            @foreach ($import_headers as $columnIndex => $header)
                                <option value="{{ $columnIndex }}">{{ $header }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="import_mapping.jersey_number" :label="__('Jersey number column (optional)')">
                            <option value="">{{ __('Not mapped') }}</option>
                            @foreach ($import_headers as $columnIndex => $header)
                                <option value="{{ $columnIndex }}">{{ $header }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if ($import_preview_rows !== [])
                        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                                    <tr>
                                        @foreach ($import_headers as $header)
                                            <th class="px-3 py-2">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($import_preview_rows as $previewRow)
                                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                            @foreach (array_keys($import_headers) as $columnIndex)
                                                <td class="px-3 py-2">{{ (string) ($previewRow[$columnIndex] ?? '') }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:button variant="primary" wire:click="importPlayers">{{ __('Import') }}</flux:button>
                    </div>
                </div>
            @endif

            @if (array_sum($import_counts) > 0)
                <div class="grid gap-2 rounded-xl border border-neutral-200 p-4 text-sm dark:border-neutral-700 sm:grid-cols-5">
                    <flux:text>{{ __('Imported') }}: {{ $import_counts['imported'] }}</flux:text>
                    <flux:text>{{ __('Updated') }}: {{ $import_counts['updated'] }}</flux:text>
                    <flux:text>{{ __('Assigned') }}: {{ $import_counts['assigned'] }}</flux:text>
                    <flux:text>{{ __('Skipped') }}: {{ $import_counts['skipped'] }}</flux:text>
                    <flux:text>{{ __('Errors') }}: {{ $import_counts['errors'] }}</flux:text>
                </div>
            @endif

            @if ($import_errors !== [])
                <div class="space-y-2 rounded-xl border border-red-200 p-4 dark:border-red-800">
                    <flux:heading size="sm">{{ __('Import errors') }}</flux:heading>

                    @foreach ($import_errors as $error)
                        <flux:text class="!text-red-600 !dark:text-red-400">{{ $error }}</flux:text>
                    @endforeach
                </div>
            @endif
        </div>
    </flux:modal>

    <div class="mt-6 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Number') }}</th>
                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->players as $player)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-3">{{ $player->first_name }} {{ $player->last_name }}</td>
                        <td class="px-4 py-3">{{ $player->number ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" :href="route('players.edit', $player)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deletePlayer({{ $player->id }})" wire:confirm="{{ __('Delete this player?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-neutral-500" colspan="3">{{ __('No players yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
