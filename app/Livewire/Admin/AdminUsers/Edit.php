<?php

namespace App\Livewire\Admin\AdminUsers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Platform Admin')]
class Edit extends Component
{
    #[Locked]
    public int $userId;

    public string $name = '';

    public string $email = '';

    public bool $is_disabled = false;

    public function mount(User $user): void
    {
        Gate::authorize('manage-platform-admin-users');

        abort_if(! $user->isPlatformAdmin(), 404);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_disabled = $user->disabled_at !== null;
    }

    public function save(): void
    {
        Gate::authorize('manage-platform-admin-users');

        $admin = User::query()->where('is_platform_admin', true)->findOrFail($this->userId);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'is_disabled' => ['required', 'boolean'],
        ]);

        if ($validated['is_disabled'] && $this->wouldLeaveNoActiveAdmins($admin)) {
            $this->addError('is_disabled', 'You cannot disable the last active platform admin.');

            return;
        }

        $admin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'disabled_at' => $validated['is_disabled'] ? now() : null,
            'current_organization_id' => $validated['is_disabled'] ? null : $admin->current_organization_id,
        ]);

        session()->flash('status', 'Admin user updated.');
    }

    public function demote(): void
    {
        Gate::authorize('manage-platform-admin-users');

        $admin = User::query()->where('is_platform_admin', true)->findOrFail($this->userId);

        if ($this->wouldLeaveNoActiveAdmins($admin)) {
            $this->addError('demote', 'You cannot demote the last active platform admin.');

            return;
        }

        $admin->update(['is_platform_admin' => false]);

        $this->redirect(route('admin.admin-users.index', absolute: false));
    }

    public function render(): View
    {
        return view('livewire.admin.admin-users.edit');
    }

    private function wouldLeaveNoActiveAdmins(User $targetAdmin): bool
    {
        $activeAdminCount = User::query()
            ->where('is_platform_admin', true)
            ->whereNull('disabled_at')
            ->count();

        return $activeAdminCount <= 1 && $targetAdmin->id === Auth::id();
    }
}
