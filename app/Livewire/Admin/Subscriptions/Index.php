<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Admin Subscriptions')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $subscriptionForms = [];

    public function mount(): void
    {
        Gate::authorize('manage-platform-saas');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function saveSubscription(int $organizationId): void
    {
        Gate::authorize('manage-platform-saas');

        $organization = Organization::query()->findOrFail($organizationId);
        $form = $this->subscriptionForms[$organizationId] ?? [];

        $validated = validator($form, [
            'stripe_subscription_id' => ['nullable', 'string', 'max:255'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'plan_code' => ['nullable', Rule::enum(BillingPlan::class)],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date'],
            'cancel_at' => ['nullable', 'date'],
            'canceled_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'metadata_json' => ['nullable', 'json'],
        ])->validate();

        DB::transaction(function () use ($organization, $organizationId, $validated): void {
            $subscription = Subscription::query()
                ->where('organization_id', $organization->id)
                ->latest('id')
                ->first();

            $metadata = $validated['metadata_json'] ?? null;
            $status = $validated['status'] instanceof SubscriptionStatus ? $validated['status']->value : $validated['status'];
            $planCode = $validated['plan_code'] ?? null;
            $planCodeValue = $planCode instanceof BillingPlan ? $planCode->value : $planCode;

            $payload = [
                'stripe_subscription_id' => $validated['stripe_subscription_id'] ?: ($subscription?->stripe_subscription_id ?? 'local_'.$organizationId.'_'.Str::uuid()->toString()),
                'stripe_price_id' => $validated['stripe_price_id'] ?? null,
                'plan_code' => $planCodeValue,
                'status' => $status,
                'quantity' => $validated['quantity'] ?? null,
                'current_period_start' => $validated['current_period_start'] ?? null,
                'current_period_end' => $validated['current_period_end'] ?? null,
                'cancel_at' => $validated['cancel_at'] ?? null,
                'canceled_at' => $validated['canceled_at'] ?? null,
                'trial_ends_at' => $validated['trial_ends_at'] ?? null,
                'metadata' => is_string($metadata) ? json_decode($metadata, true, 512, JSON_THROW_ON_ERROR) : null,
            ];

            if ($subscription === null) {
                Subscription::query()->create([
                    'organization_id' => $organization->id,
                    ...$payload,
                ]);
            } else {
                if (($validated['stripe_subscription_id'] ?? null) === null || ($validated['stripe_subscription_id'] ?? '') === '') {
                    unset($payload['stripe_subscription_id']);
                }

                $subscription->update($payload);
            }

            $organization->update([
                'subscription_plan' => $planCodeValue,
                'subscription_status' => $status,
                'trial_ends_at' => $payload['trial_ends_at'],
                'subscription_ends_at' => $payload['current_period_end'],
            ]);
        });

        session()->flash('status', 'Subscription saved.');
    }

    public function planOptions(): array
    {
        return array_map(fn (BillingPlan $plan): array => [
            'value' => $plan->value,
            'label' => ucfirst($plan->value),
        ], BillingPlan::cases());
    }

    public function subscriptionStatusOptions(): array
    {
        return array_map(fn (SubscriptionStatus $status): array => [
            'value' => $status->value,
            'label' => ucfirst(str_replace('_', ' ', $status->value)),
        ], SubscriptionStatus::cases());
    }

    public function render(): View
    {
        Gate::authorize('manage-platform-saas');

        $search = $this->search;

        $organizations = Organization::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(25);

        $latestSubscriptions = Subscription::query()
            ->whereIn('organization_id', $organizations->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('organization_id')
            ->keyBy('organization_id');

        $this->primeSubscriptionForms($organizations, $latestSubscriptions);

        return view('livewire.admin.subscriptions.index', [
            'organizations' => $organizations,
            'latestSubscriptions' => $latestSubscriptions,
        ]);
    }

    private function primeSubscriptionForms(LengthAwarePaginator $organizations, Collection $latestSubscriptions): void
    {
        foreach ($organizations->items() as $organization) {
            if (! $organization instanceof Organization || array_key_exists($organization->id, $this->subscriptionForms)) {
                continue;
            }

            $subscription = $latestSubscriptions->get($organization->id);

            if (! $subscription instanceof Subscription) {
                $subscription = null;
            }

            $this->subscriptionForms[$organization->id] = [
                'stripe_subscription_id' => $subscription?->stripe_subscription_id,
                'stripe_price_id' => $subscription?->stripe_price_id,
                'plan_code' => $subscription?->plan_code?->value,
                'status' => $subscription?->status?->value ?? ($organization->subscription_status?->value ?? SubscriptionStatus::Active->value),
                'quantity' => $subscription?->quantity,
                'current_period_start' => $subscription?->current_period_start?->format('Y-m-d\\TH:i'),
                'current_period_end' => $subscription?->current_period_end?->format('Y-m-d\\TH:i'),
                'cancel_at' => $subscription?->cancel_at?->format('Y-m-d\\TH:i'),
                'canceled_at' => $subscription?->canceled_at?->format('Y-m-d\\TH:i'),
                'trial_ends_at' => $subscription?->trial_ends_at?->format('Y-m-d\\TH:i'),
                'metadata_json' => $subscription?->metadata === null ? null : json_encode($subscription->metadata, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            ];
        }
    }
}
