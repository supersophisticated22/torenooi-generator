<section class="mx-auto w-full max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
    <div class="space-y-8">
        <div class="space-y-4 rounded-2xl border border-[#d8dbe5] bg-[#f4f6fb] p-5 sm:p-7">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#2c56c7]">{{ __('Tournament Setup') }}</p>
                    <h1 class="mt-1 text-3xl font-semibold text-[#1f2537]">{{ __('Tournament Foundation') }}</h1>
                    <p class="mt-1 text-sm text-[#5f6b88]">{{ __('Step :current of :total', ['current' => $currentStep, 'total' => count($this->steps())]) }}</p>
                </div>
                <p class="text-2xl font-semibold text-[#1550e5]">{{ $this->completionPercentage() }}% {{ __('Complete') }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ($this->steps() as $step)
                    @php($isCurrent = $currentStep === $step['id'])
                    @php($isCompleted = $currentStep > $step['id'])
                    <button type="button" wire:click="jumpToStep({{ $step['id'] }})" class="space-y-2 text-left">
                        <div class="h-2 rounded-full {{ $isCurrent ? 'bg-[#1550e5]' : ($isCompleted ? 'bg-[#7d9ff3]' : 'bg-[#d4d8e3]') }}"></div>
                        <p class="text-lg {{ $isCurrent ? 'font-semibold text-[#1f2537]' : 'text-[#5f6b88]' }}">{{ __($step['label']) }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        @error('plan')
            <flux:callout variant="danger" icon="x-circle">
                <div class="space-y-2">
                    <p>{{ $message }}</p>

                    @can('manage-organization-billing')
                        <flux:button size="sm" :href="route('billing.show')" wire:navigate>{{ __('Upgrade plan') }}</flux:button>
                    @else
                        <p class="text-sm">{{ __('Please contact your organization admin to upgrade the plan.') }}</p>
                    @endcan
                </div>
            </flux:callout>
        @enderror

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-5 rounded-2xl border border-[#d8dbe5] bg-white p-5 shadow-sm sm:p-7">
                @if ($currentStep === 1)
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-2xl font-semibold text-[#1f2537]">{{ __('Basic Info') }}</h2>
                            <div class="flex flex-wrap gap-2">
                                <flux:modal.trigger name="quick-create-event">
                                    <flux:button type="button" size="sm" variant="filled">{{ __('New Event') }}</flux:button>
                                </flux:modal.trigger>
                                <flux:modal.trigger name="quick-create-sport">
                                    <flux:button type="button" size="sm" variant="filled">{{ __('New Sport') }}</flux:button>
                                </flux:modal.trigger>
                                <flux:modal.trigger name="quick-create-category">
                                    <flux:button type="button" size="sm" variant="filled">{{ __('New Category') }}</flux:button>
                                </flux:modal.trigger>
                            </div>
                        </div>

                        <flux:input wire:model="name" :label="__('Tournament name')" type="text" required autofocus placeholder="{{ __('e.g. Summer Pro Invitational 2026') }}" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:select wire:model="event_id" :label="__('Event')" required>
                                <option value="">{{ __('Select event') }}</option>
                                @foreach ($this->events as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model.live="sport_id" :label="__('Sport')" required>
                                <option value="">{{ __('Select sport') }}</option>
                                @foreach ($this->sports as $sport)
                                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:select wire:model.live="category_id" :label="__('Category (optional)')">
                                <option value="">{{ __('No category') }}</option>
                                @foreach ($this->categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </flux:select>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:input wire:model="scheduled_start_at" :label="__('Start date')" type="datetime-local" />
                                <flux:input wire:model="scheduled_end_at" :label="__('End date')" type="datetime-local" />
                            </div>
                        </div>
                    </div>
                @endif

                @if ($currentStep === 2)
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-2xl font-semibold text-[#1f2537]">{{ __('Participants') }}</h2>
                            <div class="flex flex-wrap gap-2">
                                <flux:modal.trigger name="quick-create-team">
                                    <flux:button type="button" size="sm" variant="filled">{{ __('Add Team') }}</flux:button>
                                </flux:modal.trigger>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[#d8dbe5] bg-[#f7f8fc] p-4">
                            <div class="grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]">
                                <flux:select wire:model="participant_team_id" :label="__('Team')">
                                    <option value="">{{ __('Select team') }}</option>
                                    @foreach ($this->availableParticipantTeams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="participant_seed" :label="__('Seed (optional)')" type="number" min="1" max="999" />
                                <div class="flex items-end">
                                    <flux:button variant="primary" wire:click="addParticipantTeam">{{ __('Add Entry') }}</flux:button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 rounded-xl border border-[#d8dbe5] bg-white p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#1550e5]">{{ __('Import CSV') }}</p>
                                    <p class="text-sm text-[#5f6b88]">{{ __('Columns: name (required), short_name, seed') }}</p>
                                </div>
                                @if (! $this->isPaidSubscription())
                                    <span class="rounded-full bg-[#fff3ed] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#b8461b]">{{ __('Paid') }}</span>
                                @endif
                            </div>

                            @if (! $this->isPaidSubscription())
                                <p class="text-sm text-[#b8461b]">{{ __('CSV import requires a paid subscription.') }}</p>
                                @can('manage-organization-billing')
                                    <flux:button size="sm" :href="route('billing.show')" wire:navigate>{{ __('Upgrade plan') }}</flux:button>
                                @endcan
                            @endif

                            <div class="flex flex-wrap items-end gap-3">
                                <div class="grow">
                                    <label class="mb-1 block text-sm font-medium text-[#1f2537]">{{ __('CSV file') }}</label>
                                    <input type="file" wire:model="participants_csv" accept=".csv,text/csv" class="w-full rounded-lg border border-[#cfd5e4] bg-white px-3 py-2 text-sm text-[#1f2537] file:mr-3 file:rounded-md file:border-0 file:bg-[#1550e5] file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white" />
                                </div>
                                <flux:button variant="primary" wire:click="importParticipantsCsv" wire:loading.attr="disabled" wire:target="participants_csv,importParticipantsCsv">{{ __('Import') }}</flux:button>
                            </div>

                            @error('participants_csv')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            @if ($import_status)
                                <p class="text-sm font-medium text-[#1550e5]">{{ $import_status }}</p>
                            @endif

                            @if ($import_errors !== [])
                                <div class="space-y-1 rounded-lg border border-[#f1c6bd] bg-[#fff3f0] p-3 text-sm text-[#8a2e1e]">
                                    @foreach ($import_errors as $rowError)
                                        <p>{{ $rowError }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="overflow-hidden rounded-xl border border-[#d8dbe5]">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#f4f6fb] text-[#46506a]">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Team') }}</th>
                                        <th class="px-4 py-3">{{ __('Seed') }}</th>
                                        <th class="px-4 py-3">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white text-[#1f2537]">
                                    @forelse ($this->participantEntriesView as $entry)
                                        <tr class="border-t border-[#e8ebf2]">
                                            <td class="px-4 py-3">
                                                <p class="font-medium">{{ $entry['team_name'] }}</p>
                                                @if ($entry['team_short_name'])
                                                    <p class="text-xs text-[#63708d]">{{ $entry['team_short_name'] }}</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <flux:input wire:model.blur="participant_entries.{{ $entry['team_id'] }}.seed" type="number" min="1" max="999" class="max-w-28" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <flux:button size="sm" variant="danger" wire:click="removeParticipantTeam({{ $entry['team_id'] }})">{{ __('Remove') }}</flux:button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-[#63708d]" colspan="3">{{ __('No participants yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($currentStep === 3)
                    <div class="space-y-5">
                        <h2 class="text-2xl font-semibold text-[#1f2537]">{{ __('Rules & Seeding') }}</h2>

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

                        <div class="space-y-3 rounded-xl border border-[#d8dbe5] bg-[#f7f8fc] p-4">
                            <flux:heading size="sm">{{ __('Tournament card popup settings') }}</flux:heading>
                            <flux:checkbox wire:model="card_popup_enabled" :label="__('Enable card popup')" />

                            <div class="grid gap-3 sm:grid-cols-3">
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
                    </div>
                @endif

                @if ($currentStep === 4)
                    <div class="space-y-5">
                        <h2 class="text-2xl font-semibold text-[#1f2537]">{{ __('Review & Create') }}</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-[#d8dbe5] bg-[#f7f8fc] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#63708d]">{{ __('Tournament') }}</p>
                                <p class="mt-2 text-xl font-semibold text-[#1f2537]">{{ $name ?: __('Untitled Tournament') }}</p>
                                <p class="mt-1 text-sm text-[#5f6b88]">{{ $this->events->firstWhere('id', $event_id)?->name ?? __('No event selected') }}</p>
                            </div>
                            <div class="rounded-xl border border-[#d8dbe5] bg-[#f7f8fc] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#63708d]">{{ __('Participants') }}</p>
                                <p class="mt-2 text-xl font-semibold text-[#1f2537]">{{ count($participant_entries) }}</p>
                                <p class="mt-1 text-sm text-[#5f6b88]">{{ __('Ready for bracket generation') }}</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[#d8dbe5] bg-white p-4">
                            <dl class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#63708d]">{{ __('Sport') }}</dt>
                                    <dd class="text-sm text-[#1f2537]">{{ $this->sports->firstWhere('id', $sport_id)?->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#63708d]">{{ __('Category') }}</dt>
                                    <dd class="text-sm text-[#1f2537]">{{ $this->categories->firstWhere('id', $category_id)?->name ?? __('None') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#63708d]">{{ __('Type') }}</dt>
                                    <dd class="text-sm text-[#1f2537]">{{ ucfirst(str_replace('_', ' ', $type)) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#63708d]">{{ __('Status') }}</dt>
                                    <dd class="text-sm text-[#1f2537]">{{ ucfirst(str_replace('_', ' ', $status)) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-[#d8dbe5] bg-[#f7f8fc] p-4">
                            <p class="text-sm text-[#5f6b88]">{{ __('By creating this tournament, settings and participants are saved together.') }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-[#d8dbe5] bg-white p-5">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-[#b8461b]">{{ __('Quick Summary') }}</h3>
                    <div class="mt-4 space-y-2 text-sm text-[#5f6b88]">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Total participants') }}</span>
                            <span class="font-semibold text-[#1f2537]">{{ count($participant_entries) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('Current step') }}</span>
                            <span class="font-semibold text-[#1f2537]">{{ $currentStep }} / {{ count($this->steps()) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-[#d4d8e3]">
                        <div class="h-2 rounded-full bg-[#1550e5]" style="width: {{ $this->completionPercentage() }}%"></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#d8dbe5] bg-[#f8f9fd] p-5">
                    <h3 class="text-lg font-semibold text-[#1f2537]">{{ __('Wizard Guidance') }}</h3>
                    <div class="mt-3 space-y-3 text-sm text-[#5f6b88]">
                        <p>{{ __('Create missing entities directly from this wizard so you do not lose progress.') }}</p>
                        <p>{{ __('Import is ideal for large participant lists and is available on paid subscriptions.') }}</p>
                    </div>
                </div>

                @if (! $this->isPaidSubscription())
                    <div class="rounded-2xl border border-[#f1c6bd] bg-[#fff3ed] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#b8461b]">{{ __('Recommended for paid tier') }}</p>
                        <p class="mt-3 text-lg font-semibold text-[#7f2918]">{{ __('CSV Participant Import') }}</p>
                        <p class="mt-1 text-sm text-[#9a3a27]">{{ __('Upgrade to unlock bulk participant import in this wizard.') }}</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-[#d8dbe5] bg-white/95 backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div>
                @if ($currentStep > 1)
                    <flux:button wire:click="goToPreviousStep">{{ __('Back') }}</flux:button>
                @else
                    <flux:button :href="route('tournaments.index')" wire:navigate>{{ __('Back to tournaments') }}</flux:button>
                @endif
            </div>

            <div>
                @if ($currentStep < count($this->steps()))
                    <flux:button variant="primary" wire:click="goToNextStep">{{ __('Next') }}</flux:button>
                @else
                    <flux:button variant="primary" wire:click="save">{{ __('Create Tournament') }}</flux:button>
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="quick-create-event" class="max-w-xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create Event') }}</flux:heading>
                <flux:subheading>{{ __('Create and select an event without leaving the wizard.') }}</flux:subheading>
            </div>

            <flux:input wire:model="quick_event_name" :label="__('Name')" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="quick_event_starts_at" :label="__('Start datetime')" type="datetime-local" />
                <flux:input wire:model="quick_event_ends_at" :label="__('End datetime')" type="datetime-local" />
            </div>
            <flux:select wire:model="quick_event_status" :label="__('Status')">
                @foreach ($this->eventStatusOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createQuickEvent" x-data x-on:click="$dispatch('close-modal', 'quick-create-event')">{{ __('Create') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="quick-create-sport" x-on:quick-sport-created.window="$dispatch('close-modal', 'quick-create-sport')" class="max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create Sport') }}</flux:heading>
                <flux:subheading>{{ __('Create and immediately select a sport.') }}</flux:subheading>
            </div>

            <flux:input wire:model="quick_sport_name" :label="__('Sport name')" required />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createQuickSport">{{ __('Create') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="quick-create-category" class="max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create Category') }}</flux:heading>
                <flux:subheading>{{ __('Add a new category linked to this organization.') }}</flux:subheading>
            </div>

            <flux:input wire:model="quick_category_name" :label="__('Category name')" required />
            <flux:select wire:model="quick_category_sport_id" :label="__('Sport (optional)')">
                <option value="">{{ __('No sport restriction') }}</option>
                @foreach ($this->sports as $sport)
                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createQuickCategory" x-data x-on:click="$dispatch('close-modal', 'quick-create-category')">{{ __('Create') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="quick-create-team" x-on:quick-team-created.window="$dispatch('close-modal', 'quick-create-team')" class="max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create Team') }}</flux:heading>
                <flux:subheading>{{ __('Create a team and add it to participants directly.') }}</flux:subheading>
            </div>

            <flux:input wire:model="quick_team_name" :label="__('Team name')" required />
            <flux:input wire:model="quick_team_short_name" :label="__('Short name (optional)')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createQuickTeam">{{ __('Create') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
