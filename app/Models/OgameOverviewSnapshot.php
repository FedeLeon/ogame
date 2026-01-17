<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OgameOverviewSnapshot extends Model
{
    protected $fillable = [
        'region',
        'universe_id',
        'player_id',
        'source_url',
        'status_code',
        'is_lobby',
        'fetched_at',
        'meta',
        'resources',
        'fleets',
        'planets',
        'galaxy',
        'fleetdispatch',
        'eventbox',
        'raw_html',
        'raw_galaxy_html',
        'raw_fleetdispatch_html',
        'raw_eventbox_html',
    ];

    protected $casts = [
        'is_lobby' => 'boolean',
        'fetched_at' => 'datetime',
        'meta' => 'array',
        'resources' => 'array',
        'fleets' => 'array',
        'planets' => 'array',
        'galaxy' => 'array',
        'fleetdispatch' => 'array',
        'eventbox' => 'array',
    ];
}
