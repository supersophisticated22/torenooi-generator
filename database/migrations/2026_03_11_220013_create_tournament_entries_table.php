<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('seed')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'team_id']);
            $table->unique(['tournament_id', 'player_id']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TRIGGER tournament_entries_xor_insert BEFORE INSERT ON tournament_entries WHEN ((NEW.team_id IS NULL) = (NEW.player_id IS NULL)) BEGIN SELECT RAISE(ABORT, "Exactly one of team_id or player_id must be set"); END;');
            DB::statement('CREATE TRIGGER tournament_entries_xor_update BEFORE UPDATE ON tournament_entries WHEN ((NEW.team_id IS NULL) = (NEW.player_id IS NULL)) BEGIN SELECT RAISE(ABORT, "Exactly one of team_id or player_id must be set"); END;');

            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER tournament_entries_xor_insert
            BEFORE INSERT ON tournament_entries
            FOR EACH ROW
            BEGIN
                IF ((NEW.team_id IS NULL) = (NEW.player_id IS NULL)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exactly one of team_id or player_id must be set';
                END IF;
            END;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER tournament_entries_xor_update
            BEFORE UPDATE ON tournament_entries
            FOR EACH ROW
            BEGIN
                IF ((NEW.team_id IS NULL) = (NEW.player_id IS NULL)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exactly one of team_id or player_id must be set';
                END IF;
            END;
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tournament_entries_xor_insert');
        DB::statement('DROP TRIGGER IF EXISTS tournament_entries_xor_update');

        Schema::dropIfExists('tournament_entries');
    }
};
