<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32)->default('stripe');
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('received');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');
    }
};
