<?php

namespace App\Livewire\Tournaments;

use App\Domain\Tournaments\Enums\TournamentFinalType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Enums\TournamentType;
use App\Models\Category;
use App\Models\Event;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create tournament')]
class Create extends Component
{
    public string $name = '';

    public ?int $event_id = null;

    public ?int $sport_id = null;

    public ?int $category_id = null;

    public string $type = 'half_competition';

    public ?string $final_type = null;

    public int $pool_count = 0;

    public ?int $match_duration_minutes = null;

    public ?int $break_duration_minutes = null;

    public ?int $final_break_minutes = null;

    public ?string $scheduled_start_at = null;

    public string $status = 'draft';

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_id' => ['required', Rule::exists('events', 'id')->where('organization_id', $organization->id)],
            'sport_id' => ['required', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organization->id)
                    ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $this->sport_id)),
            ],
            'type' => ['required', 'in:'.implode(',', array_column(TournamentType::cases(), 'value'))],
            'final_type' => ['nullable', 'in:'.implode(',', array_column(TournamentFinalType::cases(), 'value'))],
            'pool_count' => ['required', 'integer', 'min:0', 'max:64'],
            'match_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'break_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'final_break_minutes' => ['nullable', 'integer', 'min:0'],
            'scheduled_start_at' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_column(TournamentStatus::cases(), 'value'))],
        ]);

        Tournament::query()->create([
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
            'status' => $validated['status'],
        ]);

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
        return $this->queryByOrganization(Category::class);
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
}
