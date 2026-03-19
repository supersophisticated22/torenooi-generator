<?php

namespace App\Livewire\Admin\Organizations;

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Organization')]
class Create extends Component
{
    public string $name = '';

    public string $slug = '';

    public ?string $billing_email = null;

    public string $country = 'NL';

    public string $timezone = 'Europe/Amsterdam';

    public string $locale = 'nl';

    public ?string $selected_plan = null;

    public ?string $subscription_plan = null;

    public ?string $subscription_status = null;

    public function mount(): void
    {
        Gate::authorize('manage-platform-saas');
    }

    public function save(): void
    {
        Gate::authorize('manage-platform-saas');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('organizations', 'slug')],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'country' => ['required', 'string', 'max:2'],
            'timezone' => ['required', 'string', 'max:64'],
            'locale' => ['required', 'string', 'max:8'],
            'selected_plan' => ['nullable', Rule::enum(BillingPlan::class)],
            'subscription_plan' => ['nullable', Rule::enum(BillingPlan::class)],
            'subscription_status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
        ]);

        Organization::query()->create($validated);

        $this->redirect(route('admin.organizations.index', absolute: false));
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
        return view('livewire.admin.organizations.create');
    }
}
