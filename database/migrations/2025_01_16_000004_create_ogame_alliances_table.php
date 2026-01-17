<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ogame_alliances', function (Blueprint $table) {
            $table->id();
            $table->string('universe_id', 8);
            $table->string('ogame_id', 32);
            $table->string('name');
            $table->string('tag', 16)->nullable();
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamps();

            $table->unique(['universe_id', 'ogame_id']);
            $table->index(['universe_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ogame_alliances');
    }
};
