<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\OgamePlanet;

class OgamePlayer extends Model
{
    protected $table = 'ogame_players';

    protected $fillable = [
        'universe_id',
        'ogame_id',
        'name',
        'status',
        'alliance_id',
        'last_scan_at',
    ];

    protected $casts = [
        'last_scan_at' => 'datetime',
    ];

    public function planets(): HasMany
    {
        return $this->hasMany(OgamePlanet::class, 'player_id', 'ogame_id');
    }
}
