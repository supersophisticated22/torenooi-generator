<?php

namespace App\Livewire\Admin\AdminUsers;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Platform Admin')]
class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?int $organization_id = null;

    public string $role = 'organization_admin';

    public function mount(): void
    {
        Gate::authorize('manage-platform-admin-users');
    }

    public function save(): void
    {
        Gate::authorize('manage-platform-admin-users');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'organization_id' => ['nullable', Rule::exists('organizations', 'id')],
            'role' => ['required', Rule::in(array_column(OrganizationRole::cases(), 'value'))],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
            'is_platform_admin' => true,
            'disabled_at' => null,
        ]);

        if ($validated['organization_id'] !== null) {
            $user->organizations()->syncWithoutDetaching([
                $validated['organization_id'] => ['role' => $validated['role']],
            ]);

            $user->update(['current_organization_id' => $validated['organization_id']]);
        }

        $this->redirect(route('admin.admin-users.index', absolute: false));
    }

    public function organizations()
    {
        return Organization::query()->whereNull('disabled_at')->orderBy('name')->get();
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
        return view('livewire.admin.admin-users.create');
    }
}
