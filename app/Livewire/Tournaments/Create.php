<?php

namespace App\Livewire\Tournaments;

use App\Domain\Billing\Exceptions\FeatureLimitExceededException;
use App\Domain\Billing\Services\EnsureOrganizationCanUseFeature;
use App\Domain\Billing\Services\SubscriptionLimits;
use App\Domain\Tournaments\Enums\EventStatus;
use App\Domain\Tournaments\Enums\MatchEventType;
use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Domain\Tournaments\Services\GenerateEventSlug;
use App\Models\Category;
use App\Models\Event;
use App\Models\Sport;
use App\Models\SportRule;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Title('Create tournament')]
class Create extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;

    public string $name = '';

    public ?int $event_id = null;

    public ?int $sport_id = null;

    public ?int $category_id = null;

    public ?string $scheduled_start_at = null;

    public ?string $scheduled_end_at = null;

    public string $type = 'half_competition';

    public ?string $final_type = null;

    public int $pool_count = 0;

    public ?int $match_duration_minutes = null;

    public ?int $break_duration_minutes = null;

    public ?int $final_break_minutes = null;

    public string $status = 'draft';

    public bool $card_popup_enabled = false;

    /** @var array<int, string> */
    public array $card_popup_types = [
        MatchEventType::YellowCard->value,
        MatchEventType::RedCard->value,
        MatchEventType::GreenCard->value,
    ];

    public string $card_popup_condition = 'any_card';

    public ?int $card_popup_threshold = null;

    public ?int $participant_team_id = null;

    public ?int $participant_seed = null;

    /**
     * @var array<string, array{team_id:int,seed:int|null}>
     */
    public array $participant_entries = [];

    public ?TemporaryUploadedFile $participants_csv = null;

    /** @var array<int, string> */
    public array $import_errors = [];

    public ?string $import_status = null;

    public string $quick_event_name = '';

    public ?string $quick_event_starts_at = null;

    public ?string $quick_event_ends_at = null;

    public string $quick_event_status = 'draft';

    public string $quick_sport_name = '';

    public string $quick_category_name = '';

    public ?int $quick_category_sport_id = null;

    public string $quick_team_name = '';

    public ?string $quick_team_short_name = null;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Tournament::class);
    }

    public function updatedSportId(?int $sportId): void
    {
        if ($sportId === null) {
            $this->category_id = null;
            $this->participant_team_id = null;
            $this->participant_entries = [];

            return;
        }

        if ($this->category_id !== null && ! Category::query()
            ->where('id', $this->category_id)
            ->where('organization_id', $this->organizationId())
            ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $sportId))
            ->exists()) {
            $this->category_id = null;
        }

        $this->participant_team_id = null;
        $this->participant_entries = [];
        $this->quick_category_sport_id = $sportId;
    }

    public function updatedCategoryId(?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = Category::query()
            ->where('organization_id', $this->organizationId())
            ->find($categoryId);

        if ($category === null) {
            $this->category_id = null;
            $this->participant_entries = [];
            $this->participant_team_id = null;
        }
    }

    public function goToNextStep(): void
    {
        $this->validateStep($this->currentStep);

        if ($this->currentStep < $this->stepsCount()) {
            $this->currentStep++;
        }
    }

    public function goToPreviousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function jumpToStep(int $step): void
    {
        if ($step < 1 || $step > $this->stepsCount()) {
            return;
        }

        if ($step > $this->currentStep) {
            for ($index = $this->currentStep; $index < $step; $index++) {
                $this->validateStep($index);
            }
        }

        $this->currentStep = $step;
    }

    public function addParticipantTeam(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'sport_id' => ['required', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
            'participant_team_id' => [
                'required',
                Rule::exists('teams', 'id')->where('organization_id', $organization->id),
            ],
        ]);

        $team = Team::query()
            ->where('organization_id', $organization->id)
            ->findOrFail($validated['participant_team_id']);

        if ((int) $team->sport_id !== (int) $this->sport_id) {
            $this->addError('participant_team_id', 'Selected team does not belong to the chosen sport.');

            return;
        }

        if ($this->category_id !== null && (int) $team->category_id !== (int) $this->category_id) {
            $this->addError('participant_team_id', 'Selected team does not belong to the chosen category.');

            return;
        }

        $teamKey = (string) $team->id;

        if (array_key_exists($teamKey, $this->participant_entries)) {
            $this->addError('participant_team_id', 'This team is already added to participants.');

            return;
        }

        $this->participant_entries[$teamKey] = [
            'team_id' => $team->id,
            'seed' => null,
        ];
        $this->resequenceParticipantSeeds();

        $this->participant_team_id = null;
        $this->participant_seed = null;
    }

    public function removeParticipantTeam(int $teamId): void
    {
        unset($this->participant_entries[(string) $teamId]);
        $this->resequenceParticipantSeeds();
    }

    public function reorderParticipantEntry(int $teamId, int $position): void
    {
        $entries = array_values($this->participant_entries);

        if ($entries === []) {
            return;
        }

        $currentIndex = null;

        foreach ($entries as $index => $entry) {
            if ((int) $entry['team_id'] === $teamId) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            return;
        }

        $targetIndex = max(0, min(count($entries) - 1, $position));

        if ($targetIndex === $currentIndex) {
            return;
        }

        $movedEntry = $entries[$currentIndex];
        array_splice($entries, $currentIndex, 1);
        array_splice($entries, $targetIndex, 0, [$movedEntry]);

        $this->participant_entries = $this->entriesKeyedByTeamId($entries);
        $this->resequenceParticipantSeeds();
    }

    public function createQuickEvent(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('create-tenant-record', Event::class);

        $validated = $this->validate([
            'quick_event_name' => ['required', 'string', 'max:255'],
            'quick_event_starts_at' => ['nullable', 'date'],
            'quick_event_ends_at' => ['nullable', 'date', 'after_or_equal:quick_event_starts_at'],
            'quick_event_status' => ['required', 'in:'.implode(',', array_column(EventStatus::cases(), 'value'))],
        ]);

        $event = Event::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['quick_event_name'],
            'slug' => app(GenerateEventSlug::class)->forOrganization($organization, $validated['quick_event_name']),
            'starts_at' => $this->normalizeDateTime($validated['quick_event_starts_at']),
            'ends_at' => $this->normalizeDateTime($validated['quick_event_ends_at']),
            'status' => $validated['quick_event_status'],
        ]);

        $this->event_id = $event->id;
        $this->quick_event_name = '';
        $this->quick_event_starts_at = null;
        $this->quick_event_ends_at = null;
        $this->quick_event_status = EventStatus::Draft->value;
    }

    public function createQuickSport(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('create-tenant-record', Sport::class);

        $validated = $this->validate([
            'quick_sport_name' => ['required', 'string', 'max:255'],
        ]);

        $slug = $this->makeUniqueSlug(
            $validated['quick_sport_name'],
            $organization->id,
            Sport::class,
        );

        $sport = Sport::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['quick_sport_name'],
            'slug' => $slug,
        ]);

        SportRule::query()->create([
            'organization_id' => $organization->id,
            'sport_id' => $sport->id,
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
        ]);

        $this->sport_id = $sport->id;
        $this->quick_category_sport_id = $sport->id;
        $this->quick_sport_name = '';

        $this->dispatch('quick-sport-created');
    }

    public function createQuickCategory(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('create-tenant-record', Category::class);

        $validated = $this->validate([
            'quick_category_name' => ['required', 'string', 'max:255'],
            'quick_category_sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        $category = Category::query()->create([
            'organization_id' => $organization->id,
            'sport_id' => $validated['quick_category_sport_id'],
            'name' => $validated['quick_category_name'],
            'slug' => $this->makeUniqueSlug($validated['quick_category_name'], $organization->id, Category::class),
        ]);

        if ($this->sport_id === null || $category->sport_id === null || (int) $category->sport_id === (int) $this->sport_id) {
            $this->category_id = $category->id;
        }

        $this->quick_category_name = '';
        $this->quick_category_sport_id = $this->sport_id;
    }

    public function createQuickTeam(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('create-tenant-record', Team::class);

        if ($this->sport_id === null) {
            $this->addError('quick_team_name', 'Choose a sport before creating a team.');

            return;
        }

        try {
            app(EnsureOrganizationCanUseFeature::class)->forTeamCreation($organization);
        } catch (FeatureLimitExceededException $exception) {
            $this->addError('plan', $exception->getMessage());

            return;
        }

        $validated = $this->validate([
            'quick_team_name' => ['required', 'string', 'max:255'],
            'quick_team_short_name' => ['nullable', 'string', 'max:32'],
            'sport_id' => ['required', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organization->id)
                    ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $this->sport_id)),
            ],
        ]);

        $team = Team::query()->create([
            'organization_id' => $organization->id,
            'sport_id' => $validated['sport_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['quick_team_name'],
            'short_name' => $validated['quick_team_short_name'],
        ]);

        $this->participant_team_id = $team->id;
        $this->quick_team_name = '';
        $this->quick_team_short_name = null;

        $this->dispatch('quick-team-created');
    }

    public function importParticipantsCsv(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        if (! app(SubscriptionLimits::class)->hasPaidSubscription($organization)) {
            $this->addError('participants_csv', 'CSV import requires a paid subscription.');

            return;
        }

        if ($this->sport_id === null) {
            $this->addError('participants_csv', 'Select a sport before importing participants.');

            return;
        }

        $validated = $this->validate([
            'participants_csv' => ['required', 'file', 'max:5120'],
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $validated['participants_csv'];

        $rows = array_map('str_getcsv', file($file->getRealPath()) ?: []);

        if ($rows === [] || ! isset($rows[0])) {
            $this->addError('participants_csv', 'Uploaded CSV is empty.');

            return;
        }

        $headers = array_map(
            static fn (?string $header): string => Str::of((string) $header)->trim()->lower()->toString(),
            $rows[0],
        );

        $nameIndex = array_search('name', $headers, true);
        $shortNameIndex = array_search('short_name', $headers, true);
        $seedIndex = array_search('seed', $headers, true);

        if ($nameIndex === false) {
            $this->addError('participants_csv', 'CSV must include a name column.');

            return;
        }

        $importErrors = [];
        $importedCount = 0;

        foreach (array_slice($rows, 1) as $rowOffset => $row) {
            $lineNumber = $rowOffset + 2;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $name = trim((string) ($row[$nameIndex] ?? ''));
            $shortName = $shortNameIndex === false ? null : trim((string) ($row[$shortNameIndex] ?? ''));
            $seedRaw = $seedIndex === false ? '' : trim((string) ($row[$seedIndex] ?? ''));

            if ($name === '') {
                $importErrors[] = 'Line '.$lineNumber.': name is required.';

                continue;
            }

            $seed = null;

            if ($seedRaw !== '') {
                if (! ctype_digit($seedRaw)) {
                    $importErrors[] = 'Line '.$lineNumber.': seed must be an integer.';

                    continue;
                }

                $seed = (int) $seedRaw;

                if ($seed < 1 || $seed > 999) {
                    $importErrors[] = 'Line '.$lineNumber.': seed must be between 1 and 999.';

                    continue;
                }
            }

            $team = Team::query()
                ->where('organization_id', $organization->id)
                ->where('sport_id', $this->sport_id)
                ->where('category_id', $this->category_id)
                ->where('name', $name)
                ->first();

            if ($team === null) {
                try {
                    app(EnsureOrganizationCanUseFeature::class)->forTeamCreation($organization);
                } catch (FeatureLimitExceededException $exception) {
                    $importErrors[] = 'Line '.$lineNumber.': '.$exception->getMessage();

                    continue;
                }

                $team = Team::query()->create([
                    'organization_id' => $organization->id,
                    'sport_id' => $this->sport_id,
                    'category_id' => $this->category_id,
                    'name' => $name,
                    'short_name' => $shortName !== '' ? $shortName : null,
                ]);
            }

            $teamKey = (string) $team->id;

            if (array_key_exists($teamKey, $this->participant_entries)) {
                $importErrors[] = 'Line '.$lineNumber.': team already exists in participants list.';

                continue;
            }

            $this->participant_entries[$teamKey] = [
                'team_id' => $team->id,
                'seed' => $seed,
            ];

            $importedCount++;
        }

        $this->import_errors = $importErrors;
        $this->import_status = 'Imported '.$importedCount.' participant(s).';
        $this->participants_csv = null;
        $this->resequenceParticipantSeeds();
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        try {
            app(EnsureOrganizationCanUseFeature::class)->forTournamentCreation($organization);
        } catch (FeatureLimitExceededException $exception) {
            $this->addError('plan', $exception->getMessage());

            return;
        }

        $validated = $this->validateAllSteps($organization->id);

        if ($validated['card_popup_enabled'] && $validated['card_popup_condition'] === 'threshold' && $validated['card_popup_threshold'] === null) {
            $this->addError('card_popup_threshold', 'The threshold field is required when threshold condition is selected.');

            return;
        }

        DB::transaction(function () use ($organization, $validated): void {
            $tournament = Tournament::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $validated['event_id'],
                'sport_id' => $validated['sport_id'],
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'final_type' => $validated['final_type'],
                'pool_count' => $validated['pool_count'],
                'match_duration_minutes' => $validated['match_duration_minutes'],
                'break_duration_minutes' => $validated['break_duration_minutes'],
                'final_break_minutes' => $validated['final_break_minutes'],
                'scheduled_start_at' => $this->normalizeDateTime($validated['scheduled_start_at']),
                'scheduled_end_at' => $this->normalizeDateTime($validated['scheduled_end_at']),
                'card_popup_settings' => $this->buildCardPopupSettings($validated),
                'status' => $validated['status'],
            ]);

            $entries = array_values($this->participant_entries);

            if ($entries !== []) {
                $tournament->entries()->createMany(array_map(
                    fn (array $entry): array => [
                        'organization_id' => $organization->id,
                        'team_id' => $entry['team_id'],
                        'player_id' => null,
                        'seed' => $entry['seed'],
                    ],
                    $entries,
                ));
            }
        });

        $this->redirect(route('tournaments.index', absolute: false));
    }

    #[Computed]
    public function events()
    {
        return $this->queryByOrganization(Event::class);
    }

    #[Computed]
    public function sports()
    {
        return $this->queryByOrganization(Sport::class);
    }

    #[Computed]
    public function categories()
    {
        $categories = $this->queryByOrganization(Category::class);

        if ($this->sport_id === null) {
            return $categories;
        }

        return $categories
            ->filter(fn (Category $category): bool => $category->sport_id === null || (int) $category->sport_id === (int) $this->sport_id)
            ->values();
    }

    #[Computed]
    public function availableParticipantTeams()
    {
        if ($this->sport_id === null) {
            return collect();
        }

        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        $excludedTeamIds = array_map(
            static fn (array $entry): int => $entry['team_id'],
            array_values($this->participant_entries),
        );

        return Team::query()
            ->forOrganization($organization)
            ->where('sport_id', $this->sport_id)
            ->when($this->category_id !== null, fn ($query) => $query->where('category_id', $this->category_id))
            ->whereNotIn('id', $excludedTeamIds)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function participantEntriesView(): array
    {
        if ($this->participant_entries === []) {
            return [];
        }

        $teamIds = array_map(
            static fn (array $entry): int => $entry['team_id'],
            array_values($this->participant_entries),
        );

        $teamsById = Team::query()
            ->whereIn('id', $teamIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        return array_values(array_filter(array_map(function (array $entry) use ($teamsById): ?array {
            $team = $teamsById->get($entry['team_id']);

            if ($team === null) {
                return null;
            }

            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_short_name' => $team->short_name,
                'seed' => $entry['seed'],
            ];
        }, array_values($this->participant_entries))));
    }

    public function tournamentTypeOptions(): array
    {
        return $this->enumOptions(TournamentType::cases());
    }

    public function tournamentFinalTypeOptions(): array
    {
        return $this->enumOptions(TournamentFinalType::cases());
    }

    public function tournamentStatusOptions(): array
    {
        return $this->enumOptions(TournamentStatus::cases());
    }

    public function eventStatusOptions(): array
    {
        return $this->enumOptions(EventStatus::cases());
    }

    public function cardEventTypeOptions(): array
    {
        return array_map(fn (string $value): array => [
            'value' => $value,
            'label' => ucfirst(str_replace('_', ' ', $value)),
        ], $this->cardEventTypes());
    }

    public function cardPopupConditionOptions(): array
    {
        return [
            ['value' => 'any_card', 'label' => 'Any selected card'],
            ['value' => 'threshold', 'label' => 'Threshold reached'],
        ];
    }

    public function steps(): array
    {
        return [
            ['id' => 1, 'label' => 'Basic Info'],
            ['id' => 2, 'label' => 'Participants'],
            ['id' => 3, 'label' => 'Rules & Seeding'],
            ['id' => 4, 'label' => 'Review'],
        ];
    }

    public function completionPercentage(): int
    {
        return (int) floor(($this->currentStep / $this->stepsCount()) * 100);
    }

    public function isPaidSubscription(): bool
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return false;
        }

        return app(SubscriptionLimits::class)->hasPaidSubscription($organization);
    }

    private function validateStep(int $step): void
    {
        $organizationId = $this->organizationId();

        if ($organizationId === null) {
            abort(403);
        }

        if ($step === 1) {
            $this->validate($this->basicInfoRules($organizationId));
        }

        if ($step === 2) {
            $this->validate($this->participantsRules($organizationId));
        }

        if ($step === 3) {
            $validated = $this->validate($this->rulesAndSeedingRules($organizationId));

            if (($validated['card_popup_enabled'] ?? false) && ($validated['card_popup_condition'] ?? 'any_card') === 'threshold' && ($validated['card_popup_threshold'] ?? null) === null) {
                $this->addError('card_popup_threshold', 'The threshold field is required when threshold condition is selected.');
            }
        }
    }

    private function validateAllSteps(int $organizationId): array
    {
        return $this->validate(array_merge(
            $this->basicInfoRules($organizationId),
            $this->participantsRules($organizationId),
            $this->rulesAndSeedingRules($organizationId),
        ));
    }

    private function basicInfoRules(int $organizationId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'event_id' => ['required', Rule::exists('events', 'id')->where('organization_id', $organizationId)],
            'sport_id' => ['required', Rule::exists('sports', 'id')->where('organization_id', $organizationId)],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organizationId)
                    ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $this->sport_id)),
            ],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date', 'after_or_equal:scheduled_start_at'],
        ];
    }

    private function participantsRules(int $organizationId): array
    {
        return [
            'participant_entries' => ['array'],
            'participant_entries.*.team_id' => ['required', Rule::exists('teams', 'id')->where('organization_id', $organizationId)],
            'participant_entries.*.seed' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }

    private function rulesAndSeedingRules(int $organizationId): array
    {
        return [
            'type' => ['required', 'in:'.implode(',', array_column(TournamentType::cases(), 'value'))],
            'final_type' => ['nullable', 'in:'.implode(',', array_column(TournamentFinalType::cases(), 'value'))],
            'pool_count' => ['required', 'integer', 'min:0', 'max:64'],
            'match_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'break_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'final_break_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_column(TournamentStatus::cases(), 'value'))],
            'card_popup_enabled' => ['required', 'boolean'],
            'card_popup_types' => ['array'],
            'card_popup_types.*' => ['required', 'in:'.implode(',', $this->cardEventTypes())],
            'card_popup_condition' => ['required', 'in:any_card,threshold'],
            'card_popup_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'event_id' => ['required', Rule::exists('events', 'id')->where('organization_id', $organizationId)],
            'sport_id' => ['required', Rule::exists('sports', 'id')->where('organization_id', $organizationId)],
        ];
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    private function queryByOrganization(string $modelClass)
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return $modelClass::query()->forOrganization($organization)->orderBy('name')->get();
    }

    private function enumOptions(array $cases): array
    {
        return array_map(fn ($case): array => [
            'value' => $case->value,
            'label' => ucfirst(str_replace('_', ' ', $case->value)),
        ], $cases);
    }

    /**
     * @return array<int, string>
     */
    private function cardEventTypes(): array
    {
        return [
            MatchEventType::YellowCard->value,
            MatchEventType::RedCard->value,
            MatchEventType::GreenCard->value,
        ];
    }

    /**
     * @param  array<int, array{team_id:int,seed:int|null}>  $entries
     * @return array<string, array{team_id:int,seed:int|null}>
     */
    private function entriesKeyedByTeamId(array $entries): array
    {
        $keyedEntries = [];

        foreach ($entries as $entry) {
            $keyedEntries[(string) $entry['team_id']] = $entry;
        }

        return $keyedEntries;
    }

    private function resequenceParticipantSeeds(): void
    {
        $entries = array_values($this->participant_entries);

        foreach ($entries as $index => &$entry) {
            $entry['seed'] = $index + 1;
        }

        unset($entry);

        $this->participant_entries = $this->entriesKeyedByTeamId($entries);
    }

    private function buildCardPopupSettings(array $validated): array
    {
        $cardTypes = array_values(array_unique(array_intersect($validated['card_popup_types'] ?? [], $this->cardEventTypes())));

        return [
            'enabled' => (bool) $validated['card_popup_enabled'],
            'card_types' => $cardTypes,
            'display' => [
                'condition' => $validated['card_popup_condition'],
                'threshold' => $validated['card_popup_condition'] === 'threshold'
                    ? $validated['card_popup_threshold']
                    : null,
            ],
        ];
    }

    private function stepsCount(): int
    {
        return 4;
    }

    private function organizationId(): ?int
    {
        return Auth::user()?->currentOrganization()?->id;
    }

    private function makeUniqueSlug(string $name, int $organizationId, string $modelClass): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while ($modelClass::query()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
