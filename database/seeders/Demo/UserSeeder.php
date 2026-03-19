<?php

namespace Database\Seeders\Demo;

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCatalog::users() as $demoUser) {
            $organization = Organization::query()
                ->where('slug', $demoUser['organization_slug'])
                ->firstOrFail();

            $onboardingStatus = $demoUser['organization_slug'] === DemoCatalog::ONBOARDING_ORGANIZATION_SLUG
                ? OnboardingStatus::PlanSelected
                : OnboardingStatus::OnboardingComplete;

            $user = User::query()->updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make(DemoCatalog::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                    'onboarding_status' => $onboardingStatus,
                    'is_platform_admin' => (bool) ($demoUser['is_platform_admin'] ?? false),
                ],
            );

            $user->organizations()->syncWithoutDetaching([
                $organization->id => ['role' => $demoUser['role']],
            ]);
            $user->update(['current_organization_id' => $organization->id]);
        }
    }
}
