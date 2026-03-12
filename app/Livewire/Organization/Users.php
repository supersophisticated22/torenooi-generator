<?php

namespace App\Livewire\Organization;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Organization Users')]
class Users extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $password = null;

    public string $role = 'viewer';

    public function mount(): void
    {
        Gate::authorize('manage-organization-users');
    }

    public function saveUser(): void
    {
        $organization = $this->organization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('manage-organization-users');

        $validated = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_column(OrganizationRole::cases(), 'value'))],
        ]);

        $member = User::query()->where('email', $validated['email'])->first();

        if ($member === null) {
            $newUserData = validator($validated, [
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ])->validate();

            $member = User::query()->create([
                'name' => $newUserData['name'],
                'email' => $validated['email'],
                'password' => $newUserData['password'],
                'email_verified_at' => now(),
            ]);
        }

        if ($member->id === Auth::id()) {
            $existingRole = $organization->users()
                ->where('users.id', $member->id)
                ->value('organization_user.role');

            if (is_string($existingRole) && $existingRole !== $validated['role']) {
                $this->addError('role', 'You cannot change your own role.');

                return;
            }
        }

        $organization->users()->syncWithoutDetaching([
            $member->id => ['role' => $validated['role']],
        ]);

        if ($member->current_organization_id === null) {
            $member->update(['current_organization_id' => $organization->id]);
        }

        $this->reset(['name', 'email', 'password']);
        $this->role = OrganizationRole::Viewer->value;

        session()->flash('status', 'User access updated.');
    }

    public function updateRole(int $userId, string $role): void
    {
        $organization = $this->organization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('manage-organization-users');

        $roleEnum = OrganizationRole::tryFrom($role);

        if ($roleEnum === null) {
            return;
        }

        if (Auth::id() === $userId) {
            $this->addError('role', 'You cannot change your own role.');

            return;
        }

        $organization->users()->updateExistingPivot($userId, ['role' => $roleEnum->value]);

        session()->flash('status', 'Role updated.');
    }

    public function removeUser(int $userId): void
    {
        $organization = $this->organization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('manage-organization-users');

        if (Auth::id() === $userId) {
            $this->addError('remove', 'You cannot remove your own access.');

            return;
        }

        $organization->users()->detach($userId);

        session()->flash('status', 'User removed from organization.');
    }

    #[Computed]
    public function members()
    {
        $organization = $this->organization();

        if ($organization === null) {
            return collect();
        }

        return $organization->users()
            ->orderBy('name')
            ->get();
    }

    public function roleOptions(): array
    {
        return array_map(fn (OrganizationRole $role): array => [
            'value' => $role->value,
            'label' => ucfirst(str_replace('_', ' ', $role->value)),
        ], OrganizationRole::cases());
    }

    public function render(): View
    {
        return view('livewire.organization.users');
    }

    private function organization(): ?Organization
    {
        return Auth::user()?->currentOrganization();
    }
}
