<?php

namespace App\Livewire\Onboarding;

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Auth\Enums\OrganizationRole;
use App\Models\Organization;
use App\Services\OnboardingFlow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Organization')]
class OrganizationCreate extends Component
{
    public string $name = '';

    public string $slug = '';

    public string $country = 'NL';

    public string $billing_email = '';

    public string $timezone = 'Europe/Amsterdam';

    public string $locale = 'nl';

    public ?string $primary_color = null;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        if ($user->currentOrganization() !== null) {
            if (app(OnboardingFlow::class)->isOrganizationSubscribed($user->currentOrganization())) {
                $this->redirectRoute('dashboard', navigate: true);

                return;
            }

            $this->redirectRoute('onboarding.plan', navigate: true);

            return;
        }

        $this->billing_email = $user->email;
    }

    public function updatedName(string $value): void
    {
        if ($this->slug === '' || Str::startsWith($this->slug, Str::slug($value))) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('organizations', 'slug')],
            'country' => ['required', Rule::in(['NL', 'BE'])],
            'billing_email' => ['required', 'email', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', Rule::in(['nl', 'en', 'fr'])],
            'primary_color' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ]);

        $organization = Organization::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'country' => $validated['country'],
            'billing_email' => $validated['billing_email'],
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'primary_color' => $validated['primary_color'],
        ]);

        $user->organizations()->syncWithoutDetaching([
            $organization->id => ['role' => OrganizationRole::OrganizationAdmin->value],
        ]);

        $user->forceFill([
            'current_organization_id' => $organization->id,
            'onboarding_status' => OnboardingStatus::OrganizationCreated,
        ])->save();

        $this->redirectRoute('onboarding.plan', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.onboarding.organization-create')
            ->layout('layouts.app', ['title' => 'Create Organization']);
    }
}
