<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\OgameOverviewSnapshot;
use App\Services\OgameHtmlParser;
use App\Support\OgameUniverse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncOverview extends Command
{
    protected $signature = 'ogame:sync-overview {--universe=}';

    protected $description = 'Descarga overview autenticado y guarda un snapshot en la DB';

    public function handle(): int
    {
        $universe = (string) ($this->option('universe') ?: OgameUniverse::getStoredUniverse());
        $region = OgameUniverse::getStoredRegion();

        if ($universe === '' || $region === '') {
            $this->error('Universo o region no configurado.');
            return self::FAILURE;
        }

        $cookieEncrypted = AppSetting::query()->where('key', 'ogame.auth.' . $region . '.' . $universe . '.cookie')->value('value');
        if (! $cookieEncrypted) {
            $this->error('No hay cookie guardada para este universo.');
            return self::FAILURE;
        }

        try {
            $cookieHeader = decrypt($cookieEncrypted);
        } catch (\Throwable $e) {
            $this->error('No se pudo leer la cookie guardada.');
            return self::FAILURE;
        }

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $baseUrl = rtrim(OgameUniverse::baseUrlFor($universe, $region), '/');
        $overviewUrl = $baseUrl . '/game/index.php?page=ingame&component=overview';
        $galaxyUrl = $baseUrl . '/game/index.php?page=ingame&component=galaxy';
        $fleetdispatchUrl = $baseUrl . '/game/index.php?page=ingame&component=fleetdispatch';
        $eventboxUrl = $baseUrl . '/game/index.php?page=ingame&component=eventbox';

        try {
            $response = Http::withHeaders([
                'Cookie' => $cookieHeader,
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(30)->get($overviewUrl);
        } catch (\Throwable $e) {
            $this->error('Error HTTP: ' . $e->getMessage());
            return self::FAILURE;
        }

        $body = (string) $response->body();
        $parsed = OgameHtmlParser::parseOverviewHtml($body);
        $galaxy = [];
        $fleetdispatch = [];
        $eventbox = [];
        $rawGalaxy = null;
        $rawFleetdispatch = null;
        $rawEventbox = null;

        if (! $parsed['is_lobby'] && $response->successful()) {
            try {
                $galaxyResponse = Http::withHeaders([
                    'Cookie' => $cookieHeader,
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml',
                ])->timeout(30)->get($galaxyUrl);
                $rawGalaxy = (string) $galaxyResponse->body();
                $galaxy = OgameHtmlParser::parseGalaxyHtml($rawGalaxy);
            } catch (\Throwable $e) {
                $galaxy = ['error' => $e->getMessage()];
            }

            try {
                $fleetResponse = Http::withHeaders([
                    'Cookie' => $cookieHeader,
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml',
                ])->timeout(30)->get($fleetdispatchUrl);
                $rawFleetdispatch = (string) $fleetResponse->body();
                $fleetdispatch = OgameHtmlParser::parseFleetdispatchHtml($rawFleetdispatch);
            } catch (\Throwable $e) {
                $fleetdispatch = ['error' => $e->getMessage()];
            }

            try {
                $eventResponse = Http::withHeaders([
                    'Cookie' => $cookieHeader,
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml',
                ])->timeout(30)->get($eventboxUrl);
                $rawEventbox = (string) $eventResponse->body();
                $eventbox = OgameHtmlParser::parseEventboxHtml($rawEventbox);
            } catch (\Throwable $e) {
                $eventbox = ['error' => $e->getMessage()];
            }
        }

        $snapshot = OgameOverviewSnapshot::updateOrCreate(
            [
                'region' => $region,
                'universe_id' => $universe,
            ],
            [
                'player_id' => $parsed['player_id'],
                'source_url' => $overviewUrl,
                'status_code' => $response->status(),
                'is_lobby' => $parsed['is_lobby'],
                'fetched_at' => now(),
                'meta' => $parsed['meta'] ?? null,
                'resources' => $parsed['resources'] ?? null,
                'fleets' => $parsed['fleets'] ?? null,
                'planets' => $parsed['planets'] ?? null,
                'galaxy' => $galaxy ?: null,
                'fleetdispatch' => $fleetdispatch ?: null,
                'eventbox' => $eventbox ?: null,
                'raw_html' => $body,
                'raw_galaxy_html' => $rawGalaxy,
                'raw_fleetdispatch_html' => $rawFleetdispatch,
                'raw_eventbox_html' => $rawEventbox,
            ]
        );

        if ($parsed['is_lobby']) {
            $this->warn('Respuesta de lobby, la cookie podria no ser valida.');
        }

        $this->info('Snapshot actualizado ID ' . $snapshot->id . ' (' . $response->status() . ').');
        return self::SUCCESS;
    }
}
