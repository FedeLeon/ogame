<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ogame_planets', function (Blueprint $table) {
            $table->string('moon_id', 32)->nullable()->after('player_id');
            $table->string('moon_name')->nullable()->after('moon_id');
            $table->unsignedSmallInteger('moon_size')->nullable()->after('moon_name');
        });
    }

    public function down(): void
    {
        Schema::table('ogame_planets', function (Blueprint $table) {
            $table->dropColumn(['moon_id', 'moon_name', 'moon_size']);
        });
    }
};
