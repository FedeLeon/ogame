<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OgamePlanet extends Model
{
    protected $table = 'ogame_planets';

    protected $fillable = [
        'universe_id',
        'ogame_id',
        'name',
        'galaxy',
        'system',
        'position',
        'player_id',
        'moon_id',
        'moon_name',
        'moon_size',
        'last_scan_at',
    ];

    protected $casts = [
        'last_scan_at' => 'datetime',
    ];
}
