<?php

namespace App\Livewire\Admin\Users;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit User')]
class Edit extends Component
{
    #[Locked]
    public int $userId;

    public string $name = '';

    public string $email = '';

    public ?int $current_organization_id = null;

    public bool $is_disabled = false;

    public ?int $new_organization_id = null;

    public string $new_role = 'viewer';

    /** @var array<int, string> */
    public array $membershipRoles = [];

    public function mount(User $user): void
    {
        Gate::authorize('manage-platform-saas');

        abort_if($user->isPlatformAdmin(), 404);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->current_organization_id = $user->current_organization_id;
        $this->is_disabled = $user->disabled_at !== null;

        foreach ($user->organizations as $organization) {
            $this->membershipRoles[$organization->id] = (string) $organization->pivot->role;
        }
    }

    public function save(): void
    {
        Gate::authorize('manage-platform-saas');

        $user = User::query()->where('is_platform_admin', false)->findOrFail($this->userId);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_organization_id' => ['nullable', Rule::exists('organizations', 'id')],
            'is_disabled' => ['required', 'boolean'],
        ]);

        if ($validated['current_organization_id'] !== null && ! $user->organizations()->whereKey($validated['current_organization_id'])->exists()) {
            $this->addError('current_organization_id', 'User must belong to the selected organization.');

            return;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'current_organization_id' => $validated['is_disabled'] ? null : $validated['current_organization_id'],
            'disabled_at' => $validated['is_disabled'] ? now() : null,
        ]);

        session()->flash('status', 'User updated.');
    }

    public function saveMembershipRole(int $organizationId, string $role): void
    {
        Gate::authorize('manage-platform-saas');

        $user = User::query()->where('is_platform_admin', false)->findOrFail($this->userId);

        validator(['role' => $role], [
            'role' => ['required', Rule::in(array_column(OrganizationRole::cases(), 'value'))],
        ])->validate();

        $user->organizations()->updateExistingPivot($organizationId, ['role' => $role]);

        $this->membershipRoles[$organizationId] = $role;

        session()->flash('status', 'Membership role updated.');
    }

    public function addMembership(): void
    {
        Gate::authorize('manage-platform-saas');

        $user = User::query()->where('is_platform_admin', false)->findOrFail($this->userId);

        $validated = $this->validate([
            'new_organization_id' => ['required', Rule::exists('organizations', 'id')],
            'new_role' => ['required', Rule::in(array_column(OrganizationRole::cases(), 'value'))],
        ]);

        $user->organizations()->syncWithoutDetaching([
            $validated['new_organization_id'] => ['role' => $validated['new_role']],
        ]);

        $this->membershipRoles[$validated['new_organization_id']] = $validated['new_role'];

        if ($user->current_organization_id === null && ! $this->is_disabled) {
            $user->update(['current_organization_id' => $validated['new_organization_id']]);
            $this->current_organization_id = $validated['new_organization_id'];
        }

        $this->reset('new_organization_id');
        $this->new_role = OrganizationRole::Viewer->value;

        session()->flash('status', 'Membership added.');
    }

    public function removeMembership(int $organizationId): void
    {
        Gate::authorize('manage-platform-saas');

        $user = User::query()->where('is_platform_admin', false)->findOrFail($this->userId);

        $user->organizations()->detach($organizationId);

        unset($this->membershipRoles[$organizationId]);

        if ($user->current_organization_id === $organizationId) {
            $newCurrent = $user->organizations()->value('organizations.id');
            $user->update(['current_organization_id' => $newCurrent]);
            $this->current_organization_id = $newCurrent;
        }

        session()->flash('status', 'Membership removed.');
    }

    public function organizations()
    {
        return Organization::query()->whereNull('disabled_at')->orderBy('name')->get();
    }

    public function memberships()
    {
        return User::query()->findOrFail($this->userId)->organizations()->orderBy('name')->get();
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
        return view('livewire.admin.users.edit');
    }
}
