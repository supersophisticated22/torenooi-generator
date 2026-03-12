<?php

namespace App\Livewire\Onboarding;

use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Plans\PlanCatalog;
use App\Models\Organization;
use App\Services\OnboardingFlow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Complete Payment')]
class Payment extends Component
{
    public function mount(): void
    {
        $organization = $this->organization();

        if ($organization === null) {
            $this->redirectRoute('onboarding.organization', navigate: true);

            return;
        }

        if (app(OnboardingFlow::class)->isOrganizationSubscribed($organization)) {
            $this->redirectRoute('dashboard', navigate: true);

            return;
        }

        if ($organization->selected_plan === null) {
            $this->redirectRoute('onboarding.plan', navigate: true);
        }
    }

    #[Computed]
    public function organizationModel(): ?Organization
    {
        return $this->organization();
    }

    #[Computed]
    public function selectedPlanMetadata(): ?array
    {
        $organization = $this->organization();

        if ($organization?->selected_plan === null) {
            return null;
        }

        return app(PlanCatalog::class)->plan($organization->selected_plan);
    }

    #[Computed]
    public function isAdmin(): bool
    {
        $organization = $this->organization();
        $user = Auth::user();

        if ($organization === null || $user === null) {
            return false;
        }

        return $user->hasOrganizationRole($organization->id, OrganizationRole::OrganizationAdmin);
    }

    public function render(): View
    {
        return view('livewire.onboarding.payment')
            ->layout('layouts.app', ['title' => 'Complete Payment']);
    }

    private function organization(): ?Organization
    {
        return Auth::user()?->currentOrganization();
    }
}
