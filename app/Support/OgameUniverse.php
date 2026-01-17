<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class OgameUniverse
{
    private const KEY = 'ogame.universe';
    private const REGION_KEY = 'ogame.region';

    public static function getUniverse(): string
    {
        $sessionValue = session('universe');
        if (is_string($sessionValue) && $sessionValue !== '') {
            return $sessionValue;
        }

        $stored = self::getStoredUniverse();
        if ($stored !== null) {
            return $stored;
        }

        return (string) config('ogame.default_universe');
    }

    public static function getRegion(): string
    {
        $sessionValue = session('region');
        if (is_string($sessionValue) && $sessionValue !== '') {
            return $sessionValue;
        }

        $stored = self::getStoredRegion();
        if ($stored !== null) {
            return $stored;
        }

        return (string) config('ogame.default_region');
    }

    public static function getStoredUniverse(): ?string
    {
        if (! Schema::hasTable('app_settings')) {
            return null;
        }

        $setting = AppSetting::query()->where('key', self::KEY)->first();
        $value = $setting?->value;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function getStoredRegion(): ?string
    {
        if (! Schema::hasTable('app_settings')) {
            return null;
        }

        $setting = AppSetting::query()->where('key', self::REGION_KEY)->first();
        $value = $setting?->value;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function storeUniverse(string $universe): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $universe]
        );
    }

    public static function storeRegion(string $region): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::REGION_KEY],
            ['value' => $region]
        );
    }

    public static function isConfigured(): bool
    {
        return self::getStoredUniverse() !== null && self::getStoredRegion() !== null;
    }

    public static function baseUrlFor(string $universe, ?string $region = null): string
    {
        $region = $region ?: self::getRegion();
        $baseUrlTemplate = (string) config('ogame.base_url_template');

        return str_replace(['{universe}', '{region}'], [$universe, $region], $baseUrlTemplate);
    }
}
