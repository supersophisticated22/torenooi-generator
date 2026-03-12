<?php

namespace App\Livewire\Players;

use App\Models\Player;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create player')]
class Create extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public ?int $number = null;

    public function mount(): void
    {
        Gate::authorize('create-tenant-record', Player::class);
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
            'number' => ['required', 'integer', 'min:1', Rule::unique('players', 'number')->where('organization_id', $organization->id)],
        ]);

        Player::query()->create([
            'organization_id' => $organization->id,
            'team_id' => null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'number' => $validated['number'],
        ]);

        $this->redirect(route('players.index', absolute: false));
    }
}
