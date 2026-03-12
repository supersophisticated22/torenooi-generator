<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referees', function (Blueprint $table): void {
            $table->foreignId('sport_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->index(['organization_id', 'sport_id']);
        });
    }

    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'sport_id']);
            $table->dropConstrainedForeignId('sport_id');
        });
    }
};
