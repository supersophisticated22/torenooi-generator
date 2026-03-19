<?php

namespace App\Livewire\Admin\AdminUsers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Platform Admin Users')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-platform-admin-users');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function disableAdmin(int $userId): void
    {
        Gate::authorize('manage-platform-admin-users');

        $admin = User::query()->where('is_platform_admin', true)->findOrFail($userId);

        if ($this->wouldLeaveNoActiveAdmins($admin)) {
            $this->addError('disable', 'You cannot disable the last active platform admin.');

            return;
        }

        $admin->update([
            'disabled_at' => now(),
            'current_organization_id' => null,
        ]);

        session()->flash('status', 'Admin user disabled.');
    }

    public function enableAdmin(int $userId): void
    {
        Gate::authorize('manage-platform-admin-users');

        User::query()
            ->where('is_platform_admin', true)
            ->findOrFail($userId)
            ->update(['disabled_at' => null]);

        session()->flash('status', 'Admin user enabled.');
    }

    public function demoteAdmin(int $userId): void
    {
        Gate::authorize('manage-platform-admin-users');

        $admin = User::query()->where('is_platform_admin', true)->findOrFail($userId);

        if ($this->wouldLeaveNoActiveAdmins($admin)) {
            $this->addError('demote', 'You cannot demote the last active platform admin.');

            return;
        }

        $admin->update(['is_platform_admin' => false]);

        session()->flash('status', 'Admin user demoted to regular user.');
    }

    public function render(): View
    {
        Gate::authorize('manage-platform-admin-users');

        $search = $this->search;

        return view('livewire.admin.admin-users.index', [
            'admins' => User::query()
                ->where('is_platform_admin', true)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nested) use ($search): void {
                        $nested
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('name')
                ->paginate(25),
        ]);
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
