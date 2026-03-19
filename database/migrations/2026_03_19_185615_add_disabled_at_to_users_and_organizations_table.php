<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->after('is_platform_admin');
            $table->index('disabled_at');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->after('subscription_ends_at');
            $table->index('disabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['disabled_at']);
            $table->dropColumn('disabled_at');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['disabled_at']);
            $table->dropColumn('disabled_at');
        });
    }
};
