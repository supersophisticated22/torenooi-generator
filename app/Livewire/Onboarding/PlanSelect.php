<?php

namespace App\Livewire\Onboarding;

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Auth\Enums\OrganizationRole;
use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Plans\PlanCatalog;
use App\Models\Organization;
use App\Services\OnboardingFlow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Choose Plan')]
class PlanSelect extends Component
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

        if (! $this->isAdmin($organization)) {
            abort(403);
        }
    }

    public function selectPlan(string $plan): void
    {
        $organization = $this->organization();

        if ($organization === null) {
            abort(403);
        }

        if (! $this->isAdmin($organization)) {
            abort(403);
        }

        $billingPlan = BillingPlan::tryFrom($plan);

        if ($billingPlan === null) {
            $this->addError('plan', 'Please select a valid plan.');

            return;
        }

        $organization->forceFill(['selected_plan' => $billingPlan])->save();

        Auth::user()?->forceFill(['onboarding_status' => OnboardingStatus::PlanSelected])->save();

        $this->redirectRoute('onboarding.payment', navigate: true);
    }

    #[Computed]
    public function plans(): array
    {
        return app(PlanCatalog::class)->plans();
    }

    #[Computed]
    public function selectedPlan(): ?BillingPlan
    {
        return $this->organization()?->selected_plan;
    }

    public function render(): View
    {
        return view('livewire.onboarding.plan-select')
            ->layout('layouts.app', ['title' => 'Choose Plan']);
    }

    private function organization(): ?Organization
    {
        return Auth::user()?->currentOrganization();
    }

    private function isAdmin(Organization $organization): bool
    {
        $user = Auth::user();

        return $user?->hasOrganizationRole($organization->id, OrganizationRole::OrganizationAdmin) ?? false;
    }
}
