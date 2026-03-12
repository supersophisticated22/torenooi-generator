<?php

use App\Domain\Auth\Enums\OnboardingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('onboarding_status')
                ->default(OnboardingStatus::AccountCreated->value)
                ->after('is_platform_admin');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('country', 2)->default('NL')->after('slug');
            $table->string('selected_plan')->nullable()->after('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('onboarding_status');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['country', 'selected_plan']);
        });
    }
};
