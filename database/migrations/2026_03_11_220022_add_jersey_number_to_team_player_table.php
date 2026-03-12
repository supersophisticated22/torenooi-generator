<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('team_player', 'jersey_number')) {
            return;
        }

        Schema::table('team_player', function (Blueprint $table): void {
            $table->unsignedSmallInteger('jersey_number')
                ->nullable()
                ->after('player_id');

            $table->unique(['team_id', 'jersey_number']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('team_player', 'jersey_number')) {
            return;
        }

        Schema::table('team_player', function (Blueprint $table): void {
            $table->dropUnique('team_player_team_id_jersey_number_unique');
            $table->dropColumn('jersey_number');
        });
    }
};
