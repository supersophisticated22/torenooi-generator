<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('name');
        });

        DB::table('events')
            ->select(['id', 'organization_id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($events): void {
                foreach ($events as $event) {
                    $baseSlug = Str::slug($event->name ?: 'event');
                    $slug = $baseSlug;
                    $suffix = 2;

                    while (DB::table('events')
                        ->where('organization_id', $event->organization_id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $event->id)
                        ->exists()) {
                        $slug = $baseSlug.'-'.$suffix;
                        $suffix++;
                    }

                    DB::table('events')
                        ->where('id', $event->id)
                        ->update(['slug' => $slug]);
                }
            });

        Schema::table('events', function (Blueprint $table): void {
            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropUnique('events_organization_id_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
