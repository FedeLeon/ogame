<?php

namespace App\Console\Commands;

use App\Models\OgamePlanet;
use App\Services\OgameApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ScanPlanets extends Command
{
    protected $signature = 'ogame:scan-planets {--universe=}';

    protected $description = 'Descarga universe.xml y actualiza el cache de planetas en la DB';

    public function handle(): int
    {
        $universeId = (string) ($this->option('universe') ?: \App\Support\OgameUniverse::getStoredUniverse());
        $universeId = $universeId !== '' ? $universeId : (string) config('ogame.default_universe');
        $region = \App\Support\OgameUniverse::getStoredRegion() ?: (string) config('ogame.default_region');
        $baseUrl = \App\Support\OgameUniverse::baseUrlFor($universeId, $region);
        $baseUrl = rtrim($baseUrl, '/');

        $this->info('Descargando ' . $baseUrl . '/api/universe.xml');

        try {
            $planets = app(OgameApiClient::class)->fetchUniverse($baseUrl);
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $now = now();
        $rows = [];
        $total = 0;
        $chunkSize = 1000;

        foreach ($planets as $planet) {
            $rows[] = [
                'universe_id' => $universeId,
                'ogame_id' => (string) ($planet['id'] ?? ''),
                'name' => Str::of((string) ($planet['name'] ?? ''))->trim()->toString(),
                'galaxy' => (int) ($planet['galaxy'] ?? 0),
                'system' => (int) ($planet['system'] ?? 0),
                'position' => (int) ($planet['position'] ?? 0),
                'player_id' => (string) ($planet['player_id'] ?? ''),
                'last_scan_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= $chunkSize) {
                $this->upsertChunk($rows);
                $total += count($rows);
                $rows = [];
            }
        }

        if (count($rows) > 0) {
            $this->upsertChunk($rows);
            $total += count($rows);
        }

        $this->info('Planetas procesados: ' . $total);

        return self::SUCCESS;
    }

    private function upsertChunk(array $rows): void
    {
        OgamePlanet::upsert(
            $rows,
            ['universe_id', 'ogame_id'],
            ['name', 'galaxy', 'system', 'position', 'player_id', 'last_scan_at', 'updated_at']
        );
    }
}
