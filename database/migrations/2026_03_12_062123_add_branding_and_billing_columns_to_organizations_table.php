<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('slug');
            $table->string('primary_color', 16)->nullable()->after('logo_path');
            $table->string('timezone')->default('Europe/Amsterdam')->after('primary_color');
            $table->string('locale', 8)->default('nl')->after('timezone');
            $table->string('stripe_customer_id')->nullable()->unique()->after('locale');
            $table->string('stripe_default_payment_method_id')->nullable()->after('stripe_customer_id');
            $table->string('billing_email')->nullable()->after('stripe_default_payment_method_id');
            $table->timestamp('trial_ends_at')->nullable()->after('billing_email');
            $table->string('subscription_plan')->nullable()->after('trial_ends_at');
            $table->string('subscription_status')->nullable()->after('subscription_plan');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'logo_path',
                'primary_color',
                'timezone',
                'locale',
                'stripe_customer_id',
                'stripe_default_payment_method_id',
                'billing_email',
                'trial_ends_at',
                'subscription_plan',
                'subscription_status',
                'subscription_ends_at',
            ]);
        });
    }
};
