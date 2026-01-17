<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OgameAlliance extends Model
{
    protected $table = 'ogame_alliances';

    protected $fillable = [
        'universe_id',
        'ogame_id',
        'name',
        'tag',
        'last_scan_at',
    ];

    protected $casts = [
        'last_scan_at' => 'datetime',
    ];
}
