<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Admin Users')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-platform-saas');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function disableUser(int $userId): void
    {
        Gate::authorize('manage-platform-saas');

        $user = User::query()->where('is_platform_admin', false)->findOrFail($userId);

        $user->update([
            'disabled_at' => now(),
            'current_organization_id' => null,
        ]);

        session()->flash('status', 'User disabled.');
    }

    public function enableUser(int $userId): void
    {
        Gate::authorize('manage-platform-saas');

        User::query()
            ->where('is_platform_admin', false)
            ->findOrFail($userId)
            ->update(['disabled_at' => null]);

        session()->flash('status', 'User enabled.');
    }

    public function render(): View
    {
        Gate::authorize('manage-platform-saas');

        $search = $this->search;

        return view('livewire.admin.users.index', [
            'users' => User::query()
                ->where('is_platform_admin', false)
                ->with(['currentOrganizationRelation:id,name'])
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
}
