<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\OgameOverviewSnapshot;
use App\Services\OgameHtmlParser;
use App\Support\OgameUniverse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $nextRunKey = 'ogame.sync.next_at.' . $region . '.' . $universe;
        $nextRunAt = AppSetting::query()->where('key', $nextRunKey)->value('value');
        if ($nextRunAt && now()->lessThan(\Carbon\Carbon::parse($nextRunAt))) {
            $this->info('Sync salteado hasta ' . $nextRunAt . '.');
            Log::info('OGame sync skipped', [
                'region' => $region,
                'universe' => $universe,
                'next_run_at' => $nextRunAt,
            ]);
            return self::SUCCESS;
        }

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

            $ajaxEventbox = $parsed['endpoints']['ajaxEventboxURI'] ?? null;
            if ($ajaxEventbox) {
                try {
                    $ajaxResponse = Http::withHeaders([
                        'Cookie' => $cookieHeader,
                        'User-Agent' => $userAgent,
                        'Accept' => 'application/json',
                    ])->timeout(30)->get($ajaxEventbox);
                    $json = json_decode((string) $ajaxResponse->body(), true);
                    if (is_array($json)) {
                        $eventbox = OgameHtmlParser::parseEventboxPayload($json);
                    }
                } catch (\Throwable $e) {
                    $eventbox['ajax_error'] = $e->getMessage();
                }
            }

            $eventlistLink = $parsed['endpoints']['eventlistLink'] ?? null;
            $shouldFetchEventlist = empty($eventbox)
                || (empty($eventbox['events']) && (($eventbox['friendly'] ?? 0) + ($eventbox['neutral'] ?? 0) + ($eventbox['hostile'] ?? 0)) === 0);
            if ($eventlistLink && $shouldFetchEventlist) {
                try {
                    $eventlistResponse = Http::withHeaders([
                        'Cookie' => $cookieHeader,
                        'User-Agent' => $userAgent,
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])->timeout(30)->get($eventlistLink);
                    $eventlistHtml = (string) $eventlistResponse->body();
                    $eventlistParsed = OgameHtmlParser::parseEventboxHtml($eventlistHtml);
                    if (! empty($eventlistParsed['events'])) {
                        $eventbox = $eventlistParsed;
                    }
                } catch (\Throwable $e) {
                    $eventbox['eventlist_error'] = $e->getMessage();
                }
            }
        }

        if (empty($fleetdispatch['slots']) && ! empty($parsed['fleets'])) {
            $fleetdispatch['slots'] = $parsed['fleets'];
        }
        if (! empty($eventbox['events'])) {
            $fleetdispatch['active_flights'] = count($eventbox['events']);
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
                'raw_galaxy_html' => null,
                'raw_fleetdispatch_html' => null,
                'raw_eventbox_html' => null,
            ]
        );

        $delaySeconds = random_int(300, 600);
        AppSetting::query()->updateOrCreate(
            ['key' => $nextRunKey],
            ['value' => now()->addSeconds($delaySeconds)->toDateTimeString()]
        );
        Log::info('OGame sync completed', [
            'region' => $region,
            'universe' => $universe,
            'status_code' => $response->status(),
            'is_lobby' => $parsed['is_lobby'],
            'snapshot_id' => $snapshot->id,
            'event_count' => is_array($eventbox['events'] ?? null) ? count($eventbox['events']) : null,
            'active_flights' => $fleetdispatch['active_flights'] ?? null,
            'next_run_in' => $delaySeconds,
        ]);

        if ($parsed['is_lobby']) {
            $this->warn('Respuesta de lobby, la cookie podria no ser valida.');
        }

        $this->info('Snapshot actualizado ID ' . $snapshot->id . ' (' . $response->status() . ').');
        return self::SUCCESS;
    }
}
