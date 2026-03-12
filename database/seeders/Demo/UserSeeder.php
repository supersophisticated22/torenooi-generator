<?php

namespace Database\Seeders\Demo;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', DemoCatalog::ORGANIZATION_SLUG)
            ->firstOrFail();

        foreach (DemoCatalog::users() as $demoUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make(DemoCatalog::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                ],
            );

            $user->organizations()->syncWithoutDetaching([$organization->id]);
            $user->update(['current_organization_id' => $organization->id]);
        }
    }
}
