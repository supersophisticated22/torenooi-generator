<?php

namespace App\Livewire\Players;

use App\Models\Player;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create player')]
class Create extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $email = null;

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('players', 'email')->where('organization_id', $organization->id)],
        ]);

        Player::query()->create([
            'organization_id' => $organization->id,
            'team_id' => null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        $this->redirect(route('players.index', absolute: false));
    }
}
