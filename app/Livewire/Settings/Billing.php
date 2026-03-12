<?php

namespace App\Livewire\Settings;

use App\Domain\Billing\Actions\CancelSubscription;
use App\Domain\Billing\Actions\ResumeSubscription;
use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Plans\PlanCatalog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Billing')]
class Billing extends Component
{
    public function mount(): void
    {
        Gate::authorize('manage-organization-billing');
    }

    #[Computed]
    public function organization(): Organization
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        return $organization;
    }

    #[Computed]
    public function currentSubscription(): ?Subscription
    {
        return Subscription::query()
            ->where('organization_id', $this->organization->id)
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function plans(): array
    {
        return app(PlanCatalog::class)->plans();
    }

    public function cancelSubscription(CancelSubscription $cancelSubscription): void
    {
        $subscription = $this->currentSubscription;

        if ($subscription === null) {
            return;
        }

        try {
            $cancelSubscription($this->organization, $subscription->stripe_subscription_id, atPeriodEnd: true);
        } catch (BillingException $exception) {
            $this->addError('billing', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Subscription cancellation scheduled for period end.');
    }

    public function resumeSubscription(ResumeSubscription $resumeSubscription): void
    {
        $subscription = $this->currentSubscription;

        if ($subscription === null) {
            return;
        }

        try {
            $resumeSubscription($this->organization, $subscription->stripe_subscription_id);
        } catch (BillingException $exception) {
            $this->addError('billing', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Subscription resumed successfully.');
    }

    public function render(): View
    {
        return view('livewire.settings.billing');
    }
}
