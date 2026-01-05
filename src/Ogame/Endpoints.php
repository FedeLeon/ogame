<?php
declare(strict_types=1);

namespace App\Ogame;

final class Endpoints
{
    /**
     * TTLs razonables para evitar spamear la API.
     */
    public const MAP = [
        'serverData' => ['path' => '/api/serverData.xml', 'ttl' => 86400],
        'players'    => ['path' => '/api/players.xml',    'ttl' => 86400],
        'alliances'  => ['path' => '/api/alliances.xml',  'ttl' => 86400],
        'universe'   => ['path' => '/api/universe.xml',   'ttl' => 604800],
        'highscore'  => ['path' => '/api/highscore.xml',  'ttl' => 3600],
    ];

    private function __construct() {}
}
