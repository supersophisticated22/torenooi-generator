<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->index(['organization_id', 'status', 'starts_at'], 'matches_org_status_start_idx');
            $table->index(['organization_id', 'field_id', 'starts_at'], 'matches_org_field_start_idx');
            $table->index(['organization_id', 'tournament_id', 'status'], 'matches_org_tournament_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropIndex('matches_org_status_start_idx');
            $table->dropIndex('matches_org_field_start_idx');
            $table->dropIndex('matches_org_tournament_status_idx');
        });
    }
};
