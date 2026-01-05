<?php
declare(strict_types=1);

namespace App;

final class Config
{
    public const VERSION = '1.0.0';

    // Universo 164 Argentina
    public const OGAME_BASE_URL = 'https://s164-ar.ogame.gameforge.com';

    // Cache local en archivos
    public const CACHE_DIR = __DIR__ . '/../.ogame_api_cache';

    private function __construct() {}
}
