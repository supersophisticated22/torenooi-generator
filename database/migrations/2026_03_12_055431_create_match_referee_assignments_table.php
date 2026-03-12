<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_referee_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['match_id', 'referee_id']);
            $table->index(['organization_id', 'match_id']);
            $table->index(['organization_id', 'referee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_referee_assignments');
    }
};
