<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('win_points')->default(3);
            $table->unsignedSmallInteger('draw_points')->default(1);
            $table->unsignedSmallInteger('loss_points')->default(0);
            $table->timestamps();

            $table->unique('sport_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_rules');
    }
};
