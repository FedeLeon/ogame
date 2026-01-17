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
     * @return array<string, string>
     */
    public function fetchServerData(string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/') . '/api/serverData.xml';
        $response = Http::timeout(60)->get($url);
        if (! $response->ok()) {
            throw new \RuntimeException('HTTP error ' . $response->status());
        }

        $xml = $response->body();
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            throw new \RuntimeException('XML invalido.');
        }

        $get = static function (\SimpleXMLElement $node, string $key): string {
            return isset($node->$key) ? trim((string) $node->$key) : '';
        };

        $knownKeys = [
            'name',
            'number',
            'domain',
            'language',
            'timezone',
            'timezoneOffset',
            'version',
            'speed',
            'speedFleet',
            'speedFleetPeaceful',
            'speedFleetWar',
            'speedFleetHolding',
            'speedEconomy',
            'speedResearch',
            'speedExpedition',
            'speedLifeforms',
            'galaxies',
            'systems',
            'acs',
            'rapidFire',
            'defToTF',
            'repairFactor',
            'newbieProtectionLimit',
            'newbieProtectionHigh',
            'topScore',
            'bonusFields',
            'debrisFactor',
            'debrisFactorDef',
            'deuteriumConsumption',
            'globalDeuteriumSaveFactor',
            'deuteriumInDebris',
            'darkMatterNewPlayer',
            'darkMatterNewAcount',
            'fleetIgnoreEmptySystems',
            'fleetIgnoreInactiveSystems',
            'bashingSystemEnabled',
            'bashlimit',
            'marketplaceEnabled',
        ];

        $data = [
            'timestamp' => (string) ($sx['timestamp'] ?? ''),
            'serverId' => (string) ($sx['serverId'] ?? ''),
        ];

        foreach ($knownKeys as $key) {
            $data[$key] = $get($sx, $key);
        }

        $extras = [];
        foreach ($sx->children() as $child) {
            if ($child->count() > 0) {
                continue;
            }
            $name = $child->getName();
            if (in_array($name, $knownKeys, true)) {
                continue;
            }
            $extras[$name] = trim((string) $child);
        }

        $data['extras'] = $extras;

        return $data;
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

    /**
     * @return array<int, array{id:string,position:int,score:int,ships:int}>
     */
    public function fetchHighscore(string $baseUrl, int $category, int $type, int $page): array
    {
        if (! in_array($category, [1, 2], true)) {
            throw new \InvalidArgumentException('category invalida');
        }
        if ($type < 0 || $type > 7) {
            throw new \InvalidArgumentException('type invalido');
        }
        if ($page < 1 || $page > 200) {
            throw new \InvalidArgumentException('page invalida');
        }

        $params = [
            'category' => (string) $category,
            'type' => (string) $type,
        ];
        if ($page > 1) {
            $params['page'] = (string) $page;
        }

        $url = rtrim($baseUrl, '/') . '/api/highscore.xml?' . http_build_query($params);
        $response = Http::timeout(60)->get($url);
        if (! $response->ok()) {
            throw new \RuntimeException('HTTP error ' . $response->status());
        }

        $xml = $response->body();
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            throw new \RuntimeException('XML invalido.');
        }

        $rows = [];
        $nodeName = $category === 1 ? 'player' : 'alliance';
        foreach ($sx->{$nodeName} as $row) {
            $rows[] = [
                'id' => (string) ($row['id'] ?? ''),
                'position' => (int) ($row['position'] ?? 0),
                'score' => (int) ($row['score'] ?? 0),
                'ships' => (int) ($row['ships'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{id:string,name:string,galaxy:int,system:int,position:int,player_id:string}>
     */
    public function fetchUniverse(string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/') . '/api/universe.xml';
        $response = Http::timeout(120)->get($url);
        if (! $response->ok()) {
            throw new \RuntimeException('HTTP error ' . $response->status());
        }

        $xml = $response->body();
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            throw new \RuntimeException('XML invalido.');
        }

        $planets = [];
        foreach ($sx->planet as $planet) {
            $coords = (string) ($planet['coords'] ?? '');
            $galaxy = 0;
            $system = 0;
            $position = 0;
            if ($coords !== '') {
                $parts = explode(':', $coords);
                $galaxy = isset($parts[0]) ? (int) $parts[0] : 0;
                $system = isset($parts[1]) ? (int) $parts[1] : 0;
                $position = isset($parts[2]) ? (int) $parts[2] : 0;
            }

            $moon = $planet->moon ?? null;
            $moonId = $moon ? (string) ($moon['id'] ?? '') : '';
            $moonName = $moon ? trim((string) ($moon['name'] ?? '')) : '';
            $moonSize = $moon ? (int) ($moon['size'] ?? 0) : 0;

            $planets[] = [
                'id' => (string) ($planet['id'] ?? ''),
                'name' => trim((string) ($planet['name'] ?? '')),
                'galaxy' => $galaxy,
                'system' => $system,
                'position' => $position,
                'player_id' => (string) ($planet['player'] ?? ''),
                'moon_id' => $moonId,
                'moon_name' => $moonName,
                'moon_size' => $moonSize,
            ];
        }

        return $planets;
    }
}
