<?php

namespace App\Livewire\Admin\Organizations;

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Organization')]
class Edit extends Component
{
    #[Locked]
    public int $organizationId;

    public string $name = '';

    public string $slug = '';

    public ?string $billing_email = null;

    public string $country = 'NL';

    public string $timezone = 'Europe/Amsterdam';

    public string $locale = 'nl';

    public ?string $selected_plan = null;

    public ?string $subscription_plan = null;

    public ?string $subscription_status = null;

    public bool $is_disabled = false;

    public function mount(Organization $organization): void
    {
        Gate::authorize('manage-platform-saas');

        $this->organizationId = $organization->id;
        $this->name = $organization->name;
        $this->slug = $organization->slug;
        $this->billing_email = $organization->billing_email;
        $this->country = $organization->country;
        $this->timezone = $organization->timezone;
        $this->locale = $organization->locale;
        $this->selected_plan = $organization->selected_plan?->value;
        $this->subscription_plan = $organization->subscription_plan?->value;
        $this->subscription_status = $organization->subscription_status?->value;
        $this->is_disabled = $organization->disabled_at !== null;
    }

    public function save(): void
    {
        Gate::authorize('manage-platform-saas');

        $organization = Organization::query()->findOrFail($this->organizationId);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organization->id)],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'country' => ['required', 'string', 'max:2'],
            'timezone' => ['required', 'string', 'max:64'],
            'locale' => ['required', 'string', 'max:8'],
            'selected_plan' => ['nullable', Rule::enum(BillingPlan::class)],
            'subscription_plan' => ['nullable', Rule::enum(BillingPlan::class)],
            'subscription_status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
            'is_disabled' => ['required', 'boolean'],
        ]);

        $organization->update([
            ...$validated,
            'disabled_at' => $validated['is_disabled'] ? now() : null,
        ]);

        if ($validated['is_disabled']) {
            $organization->users()->where('users.current_organization_id', $organization->id)->update(['current_organization_id' => null]);
        }

        session()->flash('status', 'Organization updated.');
    }

    public function planOptions(): array
    {
        return array_map(fn (BillingPlan $plan): array => [
            'value' => $plan->value,
            'label' => ucfirst($plan->value),
        ], BillingPlan::cases());
    }

    public function statusOptions(): array
    {
        return array_map(fn (SubscriptionStatus $status): array => [
            'value' => $status->value,
            'label' => ucfirst(str_replace('_', ' ', $status->value)),
        ], SubscriptionStatus::cases());
    }

    public function render()
    {
        return view('livewire.admin.organizations.edit');
    }
}
