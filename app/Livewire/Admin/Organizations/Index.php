<?php

namespace App\Livewire\Admin\Organizations;

use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Admin Organizations')]
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

    public function disableOrganization(int $organizationId): void
    {
        Gate::authorize('manage-platform-saas');

        $organization = Organization::query()->findOrFail($organizationId);
        $organization->update(['disabled_at' => now()]);

        if ($organization->users()->where('users.current_organization_id', $organization->id)->exists()) {
            $organization->users()->where('users.current_organization_id', $organization->id)->update(['current_organization_id' => null]);
        }

        session()->flash('status', 'Organization disabled.');
    }

    public function enableOrganization(int $organizationId): void
    {
        Gate::authorize('manage-platform-saas');

        Organization::query()->findOrFail($organizationId)->update(['disabled_at' => null]);

        session()->flash('status', 'Organization enabled.');
    }

    public function render(): View
    {
        Gate::authorize('manage-platform-saas');

        $search = $this->search;

        return view('livewire.admin.organizations.index', [
            'organizations' => Organization::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nested) use ($search): void {
                        $nested
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('slug', 'like', '%'.$search.'%')
                            ->orWhere('billing_email', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('name')
                ->paginate(25),
        ]);
    }
}
