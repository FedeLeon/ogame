<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OgameApiClient
{
    /**
     * @return array<int, array{id:string,name:string,status:string,alliance_id:string}>
     */
    public function fetchPlayers(string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/') . '/api/players.xml';
        $response = Http::timeout(60)->get($url);
        if (! $response->ok()) {
            throw new \RuntimeException('HTTP error ' . $response->status());
        }

        $xml = $response->body();
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            throw new \RuntimeException('XML invalido.');
        }

        $players = [];
        foreach ($sx->player as $player) {
            $players[] = [
                'id' => (string) ($player['id'] ?? ''),
                'name' => trim((string) ($player['name'] ?? '')),
                'status' => (string) ($player['status'] ?? ''),
                'alliance_id' => (string) ($player['alliance'] ?? ''),
            ];
        }

        return $players;
    }

    /**
     * @return array<int, array{id:string,name:string,tag:string}>
     */
    public function fetchAlliances(string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/') . '/api/alliances.xml';
        $response = Http::timeout(60)->get($url);
        if (! $response->ok()) {
            throw new \RuntimeException('HTTP error ' . $response->status());
        }

        $xml = $response->body();
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            throw new \RuntimeException('XML invalido.');
        }

        $alliances = [];
        foreach ($sx->alliance as $alliance) {
            $alliances[] = [
                'id' => (string) ($alliance['id'] ?? ''),
                'name' => trim((string) ($alliance['name'] ?? '')),
                'tag' => trim((string) ($alliance['tag'] ?? '')),
            ];
        }

        return $alliances;
    }
}
