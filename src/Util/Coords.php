<?php
declare(strict_types=1);

namespace App\Util;

final class Coords
{
    /**
     * @return array{g:int,s:int,p:int}|null
     */
    public static function parse(string $input): ?array
    {
        $s = trim($input);
        $s = str_replace(['[',']',' '], '', $s);
        if (!preg_match('/^(\d+):(\d+):(\d+)$/', $s, $m)) {
            return null;
        }
        return ['g' => (int)$m[1], 's' => (int)$m[2], 'p' => (int)$m[3]];
    }

    public static function toString(int $g, int $s, int $p): string
    {
        return sprintf('[%d:%d:%d]', $g, $s, $p);
    }

    /**
     * Distancia clásica de OGame (helper legal: cálculo)
     */
    public static function distance(array $a, array $b): int
    {
        $dg = abs($a['g'] - $b['g']);
        $ds = abs($a['s'] - $b['s']);
        $dp = abs($a['p'] - $b['p']);

        if ($dg !== 0) return 20000 * $dg;
        if ($ds !== 0) return 2700 + 95 * $ds;
        if ($dp !== 0) return 1000 + 5 * $dp;
        return 5;
    }

    private function __construct() {}
}
