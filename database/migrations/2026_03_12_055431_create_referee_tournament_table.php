<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referee_tournament', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tournament_id', 'referee_id']);
            $table->index(['organization_id', 'tournament_id']);
            $table->index(['organization_id', 'referee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referee_tournament');
    }
};
