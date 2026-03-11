<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('seed')->nullable();
            $table->timestamps();

            $table->unique(['pool_id', 'tournament_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_entries');
    }
};
