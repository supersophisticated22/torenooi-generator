<?php

namespace App\Livewire\Referees;

use App\Models\Referee;
use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create referee')]
class Create extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?int $sport_id = null;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Referee::class);
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('referees', 'email')->where('organization_id', $organization->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        Referee::query()->create([
            'organization_id' => $organization->id,
            'sport_id' => $validated['sport_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $this->redirect(route('referees.index', absolute: false));
    }

    #[Computed]
    public function sports()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Sport::query()->forOrganization($organization)->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.referees.create');
    }
}
