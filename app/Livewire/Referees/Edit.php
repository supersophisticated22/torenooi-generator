<?php

namespace App\Livewire\Referees;

use App\Models\Referee;
use App\Models\Sport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit referee')]
class Edit extends Component
{
    #[Locked]
    public int $refereeId;

    public string $first_name = '';

    public string $last_name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?int $sport_id = null;

    public function mount(Referee $referee): void
    {
        Gate::authorize('manage-tenant-record', $referee);

        $this->refereeId = $referee->id;
        $this->sport_id = $referee->sport_id;
        $this->first_name = $referee->first_name;
        $this->last_name = $referee->last_name;
        $this->email = $referee->email;
        $this->phone = $referee->phone;
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $referee = Referee::query()->findOrFail($this->refereeId);
        Gate::authorize('manage-tenant-record', $referee);

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('referees', 'email')->where('organization_id', $organization->id)->ignore($referee->id),
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'sport_id' => ['nullable', Rule::exists('sports', 'id')->where('organization_id', $organization->id)],
        ]);

        $referee->update([
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
        return view('livewire.referees.edit');
    }
}
