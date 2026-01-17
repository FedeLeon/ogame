<?php
declare(strict_types=1);

namespace App\Ogame;

use App\HttpClient;
use App\Cache\FileCache;

final class OgameApi
{
    public function __construct(
        private HttpClient $http,
        private FileCache $cache,
        private string $baseUrl
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    /**
     * @return array{data:array, meta:array}
     */
    public function serverData(): array
    {
        $xml = $this->fetchCached('serverData', []);
        $sx = $this->parseXml($xml['body']);

        $get = fn(string $k) => isset($sx->$k) ? trim((string)$sx->$k) : '';

        return [
            'data' => [
                'timestamp' => (string)($sx['timestamp'] ?? ''),
                'domain' => $get('domain'),
                'language' => $get('language'),
                'timezone' => $get('timezone'),
                'version' => $get('version'),
                'speedFleet' => $get('speedFleet'),
                'speedEconomy' => $get('speedEconomy'),
                'speedResearch' => $get('speedResearch'),
                'galaxies' => $get('galaxies'),
                'systems' => $get('systems'),
                'donutGalaxy' => $get('donutGalaxy'),
                'donutSystem' => $get('donutSystem'),
                'debrisFactor' => $get('debrisFactor'),
                'debrisFactorDef' => $get('debrisFactorDef'),
                'deuteriumConsumption' => $get('deuteriumConsumption'),
            ],
            'meta' => $xml['meta'],
        ];
    }

    /**
     * @return array{players:list<array{id:string,name:string,status:string,alliance:string}>, meta:array}
     */
    public function players(): array
    {
        $xml = $this->fetchCached('players', []);
        $sx = $this->parseXml($xml['body']);

        $out = [];
        foreach ($sx->player as $p) {
            $out[] = [
                'id' => (string)($p['id'] ?? ''),
                'name' => (string)($p['name'] ?? ''),
                'status' => (string)($p['status'] ?? ''),
                'alliance' => (string)($p['alliance'] ?? ''),
            ];
        }

        return ['players' => $out, 'meta' => $xml['meta']];
    }

    /**
     * @return array{alliances:list<array{id:string,name:string,tag:string,members:list<array{id:string,name:string,status:string}>}>, meta:array}
     */
    public function alliances(): array
    {
        $xml = $this->fetchCached('alliances', []);
        $sx = $this->parseXml($xml['body']);

        $out = [];
        foreach ($sx->alliance as $a) {
            $members = [];
            foreach ($a->player as $m) {
                $members[] = [
                    'id' => (string)($m['id'] ?? ''),
                    'name' => (string)($m['name'] ?? ''),
                    'status' => (string)($m['status'] ?? ''),
                ];
            }

            $out[] = [
                'id' => (string)($a['id'] ?? ''),
                'name' => (string)($a['name'] ?? ''),
                'tag' => (string)($a['tag'] ?? ''),
                'members' => $members,
            ];
        }

        return ['alliances' => $out, 'meta' => $xml['meta']];
    }

    /**
     * @return array{planets:list<array{id:string,name:string,galaxy:int,system:int,position:int,playerId:string}>, meta:array}
     */
    public function universe(): array
    {
        $xml = $this->fetchCached('universe', []);
        $sx = $this->parseXml($xml['body']);

        $out = [];
        foreach ($sx->planet as $pl) {
            $out[] = [
                'id' => (string)($pl['id'] ?? ''),
                'name' => (string)($pl['name'] ?? ''),
                'galaxy' => (int)($pl['galaxy'] ?? 0),
                'system' => (int)($pl['system'] ?? 0),
                'position' => (int)($pl['position'] ?? 0),
                'playerId' => (string)($pl['player'] ?? ''),
            ];
        }

        return ['planets' => $out, 'meta' => $xml['meta']];
    }

    /**
     * @return array{rows:list<array{id:string,position:int,score:int,ships:int}>, meta:array}
     */
    public function highscore(int $category, int $type, int $page): array
{
    if (!in_array($category, [1, 2], true)) {
        throw new \InvalidArgumentException('category inválida (1 players / 2 alliances)');
    }
    if ($type < 0 || $type > 7) {
        throw new \InvalidArgumentException('type inválido (0..7)');
    }
    if ($page < 1 || $page > 200) {
        throw new \InvalidArgumentException('page inválida (1..200)');
    }

    // NO mandamos page si es 1
    $params = [
        'category' => (string)$category,
        'type' => (string)$type,
    ];
    if ($page > 1) {
        $params['page'] = (string)$page;
    }

    $xml = $this->fetchCached('highscore', $params);

    $sx = $this->parseXml($xml['body']);

    $rows = [];
    $nodeName = ($category === 1) ? 'player' : 'alliance';
    foreach ($sx->{$nodeName} as $n) {
        $rows[] = [
            'id' => (string)($n['id'] ?? ''),
            'position' => (int)($n['position'] ?? 0),
            'score' => (int)($n['score'] ?? 0),
            'ships' => (int)($n['ships'] ?? 0),
        ];
    }

    return ['rows' => $rows, 'meta' => $xml['meta']];
}


    // --------------------
    // Internals
    // --------------------

    private function fetchCached(string $key, array $queryParams): array
    {
        if (!isset(Endpoints::MAP[$key])) {
            throw new \InvalidArgumentException('Endpoint no soportado: ' . $key);
        }

        $path = Endpoints::MAP[$key]['path'];
        $ttl  = (int)Endpoints::MAP[$key]['ttl'];

        $url = $this->baseUrl . $path . (count($queryParams) ? ('?' . http_build_query($queryParams)) : '');
        $cacheKey = sha1($url);

        $meta = $this->cache->metaGet($cacheKey);
        $now = time();
        $isFresh = isset($meta['saved_at']) && ($now - (int)$meta['saved_at'] <= $ttl);

        // 1) HIT fresco
        if ($isFresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return [
                    'body' => $cached,
                    'meta' => ($meta + ['cache' => 'HIT', 'url' => $url]),
                ];
            }
        }

        // 2) pedir al server
        $res = $this->http->get($url);
        if (!$res['ok']) {
            // 3) si falla (ej HTTP 500), intentamos devolver cache stale si existe
            $stale = $this->cache->get($cacheKey);
            if ($stale !== null) {
                return [
                    'body' => $stale,
                    'meta' => ($meta + [
                        'cache' => 'STALE',
                        'url' => $url,
                        'note' => 'API falló y se devolvió cache viejo',
                        'api_error' => $res['error'] ?? 'error',
                        'api_code' => $res['code'] ?? 0,
                    ]),
                ];
            }

            throw new \RuntimeException(
                'No se pudo obtener API: ' . ($res['error'] ?? 'error') . ' | URL: ' . $url
            );
        }

        // 4) guardar cache
        $this->cache->set($cacheKey, $res['body']);
        $this->cache->metaSet($cacheKey, [
            'saved_at' => $now,
            'url' => $url,
            'ttl' => $ttl,
        ]);

        return [
            'body' => $res['body'],
            'meta' => ['saved_at' => $now, 'url' => $url, 'ttl' => $ttl, 'cache' => 'MISS'],
        ];
    }

    private function parseXml(string $xml): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        if ($sx === false) {
            $errs = libxml_get_errors();
            libxml_clear_errors();
            throw new \RuntimeException('XML inválido. ' . (isset($errs[0]) ? trim($errs[0]->message) : ''));
        }
        return $sx;
    }

    public function raw(string $endpointKey, array $queryParams = [], bool $toJson = false): array
{
    if (!isset(Endpoints::MAP[$endpointKey])) {
        throw new \InvalidArgumentException('endpointKey inválido');
    }

    if ($endpointKey === 'highscore') {
        $category = (int)($queryParams['category'] ?? 1);
        $type = (int)($queryParams['type'] ?? 0);
        $page = (int)($queryParams['page'] ?? 1);

        $queryParams = [
            'category' => (string)$category,
            'type' => (string)$type,
        ];
        // NO page si es 1
        if ($page > 1) {
            $queryParams['page'] = (string)$page;
        }
    } else {
        $queryParams = [];
    }

    if ($toJson) {
        $queryParams['toJson'] = '1';
    }

    $path = Endpoints::MAP[$endpointKey]['path'];
    $url = $this->baseUrl . $path . (count($queryParams) ? ('?' . http_build_query($queryParams)) : '');

    $cacheKey = sha1('raw:' . $url);
    $ttl = 30;

    $meta = $this->cache->metaGet($cacheKey);
    $now = time();
    $isFresh = isset($meta['saved_at']) && ($now - (int)$meta['saved_at'] <= $ttl);

    if ($isFresh) {
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return ['body' => $cached, 'meta' => $meta + ['cache' => 'HIT', 'url' => $url]];
        }
    }

    $res = $this->http->get($url);
    if (!$res['ok']) {
        throw new \RuntimeException('HTTP fetch falló: ' . ($res['error'] ?? 'error') . ' | URL: ' . $url);
    }

    $this->cache->set($cacheKey, $res['body']);
    $this->cache->metaSet($cacheKey, [
        'saved_at' => $now,
        'url' => $url,
        'ttl' => $ttl,
        'cache' => 'MISS'
    ]);

    return ['body' => $res['body'], 'meta' => ['saved_at' => $now, 'url' => $url, 'ttl' => $ttl, 'cache' => 'MISS']];
}

}
