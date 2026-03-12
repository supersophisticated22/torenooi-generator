<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('teams', 'sport_id')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table): void {
            $table->foreignId('sport_id')
                ->nullable()
                ->after('organization_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('teams', 'sport_id')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sport_id');
        });
    }
};
