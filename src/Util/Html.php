<?php
declare(strict_types=1);

namespace App\Util;

final class Html
{
    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string,string|int|float|null> $params
     */
    public static function url(string $page, array $params = []): string
    {
        $base = ['page' => $page] + $params;
        return '/index.php?' . http_build_query($base);
    }

    private function __construct() {}
}
