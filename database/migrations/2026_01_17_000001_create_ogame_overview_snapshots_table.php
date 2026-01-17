<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ogame_overview_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('region', 8);
            $table->string('universe_id', 16);
            $table->string('player_id', 32)->nullable();
            $table->string('source_url');
            $table->unsignedInteger('status_code')->default(0);
            $table->boolean('is_lobby')->default(false);
            $table->timestamp('fetched_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('resources')->nullable();
            $table->json('fleets')->nullable();
            $table->json('planets')->nullable();
            $table->json('galaxy')->nullable();
            $table->json('fleetdispatch')->nullable();
            $table->json('eventbox')->nullable();
            $table->longText('raw_html')->nullable();
            $table->longText('raw_galaxy_html')->nullable();
            $table->longText('raw_fleetdispatch_html')->nullable();
            $table->longText('raw_eventbox_html')->nullable();
            $table->timestamps();

            $table->index(['region', 'universe_id', 'fetched_at']);
            $table->unique(['region', 'universe_id'], 'ogame_overview_snapshots_unique_region_universe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ogame_overview_snapshots');
    }
};
