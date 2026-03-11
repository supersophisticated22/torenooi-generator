<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedSmallInteger('home_score')->default(0);
            $table->unsignedSmallInteger('away_score')->default(0);
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('match_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
