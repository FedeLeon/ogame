<?php
declare(strict_types=1);

namespace App\Cache;

final class FileCache
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }
    }

    public function get(string $key): ?string
    {
        $file = $this->dataPath($key);
        if (!is_file($file)) return null;
        return (string)file_get_contents($file);
    }

    public function set(string $key, string $value): void
    {
        @file_put_contents($this->dataPath($key), $value);
    }

    public function metaGet(string $key): array
    {
        $file = $this->metaPath($key);
        if (!is_file($file)) return [];
        $raw = (string)file_get_contents($file);
        $j = json_decode($raw, true);
        return is_array($j) ? $j : [];
    }

    public function metaSet(string $key, array $meta): void
    {
        @file_put_contents($this->metaPath($key), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function dataPath(string $key): string
    {
        return rtrim($this->dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.cache';
    }

    private function metaPath(string $key): string
    {
        return rtrim($this->dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.meta.json';
    }
}
