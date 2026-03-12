<?php

namespace App\Livewire\Players;

use App\Models\Player;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit player')]
class Edit extends Component
{
    #[Locked]
    public int $playerId;

    public string $first_name = '';

    public string $last_name = '';

    public ?string $email = null;

    public function mount(Player $player): void
    {
        Gate::authorize('manage-tenant-record', $player);

        $this->playerId = $player->id;
        $this->first_name = $player->first_name;
        $this->last_name = $player->last_name;
        $this->email = $player->email;
    }

    public function save(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $player = Player::query()->findOrFail($this->playerId);
        Gate::authorize('manage-tenant-record', $player);

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('players', 'email')->where('organization_id', $organization->id)->ignore($player->id),
            ],
        ]);

        $player->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        $this->redirect(route('players.index', absolute: false));
    }
}
