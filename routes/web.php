<?php

use App\Support\OgameUniverse;
use App\Models\AppSetting;
use App\Models\OgameAlliance;
use App\Models\OgamePlanet;
use App\Models\OgamePlayer;
use App\Models\OgameOverviewSnapshot;
use App\Services\OgameApiClient;
use App\Services\OgameHtmlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function (Request $request, OgameApiClient $apiClient) {
    $serverData = null;
    $serverError = null;
    $selfPlayer = null;
    $selfPlanets = [];
    $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $authData = [
        'cookie_header' => '',
    ];

    if (OgameUniverse::isConfigured()) {
        $universe = OgameUniverse::getUniverse();
        $region = OgameUniverse::getRegion();
        $baseUrl = OgameUniverse::baseUrlFor($universe, $region);

        $dataKey = 'ogame.server_data.' . $region . '.' . $universe;
        $scanKey = 'ogame.server_data_last_scan_at.' . $region . '.' . $universe;
        $lastScanValue = AppSetting::query()->where('key', $scanKey)->value('value');
        $lastScan = $lastScanValue ? Carbon::parse($lastScanValue) : null;
        $needsSync = ! $lastScan || $lastScan->diffInHours(now()) >= 6;

        if ($request->boolean('refresh') || $needsSync) {
            try {
                $serverData = $apiClient->fetchServerData($baseUrl);
                AppSetting::query()->updateOrCreate(
                    ['key' => $dataKey],
                    ['value' => json_encode($serverData)]
                );
                AppSetting::query()->updateOrCreate(
                    ['key' => $scanKey],
                    ['value' => now()->toIso8601String()]
                );
            } catch (\Throwable $e) {
                $serverError = $e->getMessage();
            }
        } else {
            $cached = AppSetting::query()->where('key', $dataKey)->value('value');
            if ($cached) {
                $serverData = json_decode($cached, true);
            }
        }

        $selfKey = 'ogame.self_player_id.' . $region . '.' . $universe;
        $selfId = AppSetting::query()->where('key', $selfKey)->value('value');
        if ($selfId) {
            $selfPlayer = OgamePlayer::query()
                ->where('universe_id', $universe)
                ->where('ogame_id', $selfId)
                ->withCount('planets')
                ->first();

            if ($selfPlayer) {
                $selfPlanets = OgamePlanet::query()
                    ->where('universe_id', $universe)
                    ->where('player_id', $selfPlayer->ogame_id)
                    ->orderBy('galaxy')
                    ->orderBy('system')
                    ->orderBy('position')
                    ->get(['galaxy', 'system', 'position'])
                    ->map(function ($planet) {
                        return $planet->galaxy . ':' . $planet->system . ':' . $planet->position;
                    })
                    ->values()
                    ->all();
            }
        }

        $authKeyPrefix = 'ogame.auth.' . $region . '.' . $universe;
        $cookieEncrypted = AppSetting::query()->where('key', $authKeyPrefix . '.cookie')->value('value');
        if ($cookieEncrypted) {
            try {
                $authData['cookie_header'] = decrypt($cookieEncrypted);
            } catch (Throwable $e) {
                $authData['cookie_header'] = '';
            }
        }
        $authData['user_agent'] = $defaultUserAgent;
    }

    return view('pages.home', [
        'serverData' => $serverData,
        'serverError' => $serverError,
        'selfPlayer' => $selfPlayer,
        'selfPlanets' => $selfPlanets,
        'authData' => $authData,
        'defaultUserAgent' => $defaultUserAgent,
    ]);
})->name('home');

Route::get('/login', function () {
    $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $authData = [
        'cookie_header' => '',
    ];

    if (OgameUniverse::isConfigured()) {
        $universe = OgameUniverse::getUniverse();
        $region = OgameUniverse::getRegion();
        $authKeyPrefix = 'ogame.auth.' . $region . '.' . $universe;
        $cookieEncrypted = AppSetting::query()->where('key', $authKeyPrefix . '.cookie')->value('value');
        if ($cookieEncrypted) {
            try {
                $authData['cookie_header'] = decrypt($cookieEncrypted);
            } catch (Throwable $e) {
                $authData['cookie_header'] = '';
            }
        }
        $authData['user_agent'] = $defaultUserAgent;
    }

    return view('pages.login', [
        'authData' => $authData,
        'defaultUserAgent' => $defaultUserAgent,
    ]);
})->name('login');
Route::post('/login', function () {
    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();

    $cookieHeader = trim((string) request('cookie_header', ''));
    $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $userAgent = $defaultUserAgent;

    if ($cookieHeader === '') {
        return back()->withInput()->with('status', 'Falta el header Cookie.');
    }

    $keyPrefix = 'ogame.auth.' . $region . '.' . $universe;
    AppSetting::query()->updateOrCreate(
        ['key' => $keyPrefix . '.cookie'],
        ['value' => encrypt($cookieHeader)]
    );

    AppSetting::query()->updateOrCreate(
        ['key' => $keyPrefix . '.user_agent'],
        ['value' => $userAgent]
    );

    if (OgameUniverse::isConfigured()) {
        \Artisan::call('ogame:sync-overview', ['--force' => true]);
    }

    return back()->with('status', 'Credenciales guardadas.');
})->name('login.submit');

Route::post('/login/test', function (Request $request) {
    if (! OgameUniverse::isConfigured()) {
        return back()->with('status', 'Configura tu universo primero.');
    }

    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();
    $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    $cookieHeader = trim((string) $request->input('cookie_header', ''));
    $userAgent = $defaultUserAgent;

    $keyPrefix = 'ogame.auth.' . $region . '.' . $universe;
    if ($cookieHeader === '') {
        $cookieEncrypted = AppSetting::query()->where('key', $keyPrefix . '.cookie')->value('value');
        if ($cookieEncrypted) {
            try {
                $cookieHeader = decrypt($cookieEncrypted);
            } catch (Throwable $e) {
                $cookieHeader = '';
            }
        }
    }

    if ($cookieHeader === '') {
        return back()->withInput()->with('status', 'Falta el header Cookie.');
    }

    $testUrl = OgameUniverse::baseUrlFor($universe, $region) . '/game/index.php?page=ingame&component=overview';
    try {
        $response = Http::withHeaders([
            'Cookie' => $cookieHeader,
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml',
        ])->timeout(30)->get($testUrl);
    } catch (Throwable $e) {
        return back()->withInput()->with('login_test', [
            'ok' => false,
            'status' => 'error',
            'login_detected' => '—',
            'snippet' => 'Error: ' . $e->getMessage(),
        ]);
    }

    $body = (string) $response->body();
    $parsed = OgameHtmlParser::parseOverviewHtml($body);
    $loginDetected = $parsed['is_lobby'] || Str::contains($body, 'page=login') || Str::contains($body, 'login');
    $snippet = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($body))), 400, '...');
    $details = [];

    $resourcesSummary = '';
    if (! empty($parsed['resources'])) {
        $parts = [];
        if (isset($parsed['resources']['metal'])) {
            $parts[] = 'M ' . number_format((int) $parsed['resources']['metal']);
        }
        if (isset($parsed['resources']['crystal'])) {
            $parts[] = 'C ' . number_format((int) $parsed['resources']['crystal']);
        }
        if (isset($parsed['resources']['deuterium'])) {
            $parts[] = 'D ' . number_format((int) $parsed['resources']['deuterium']);
        }
        $resourcesSummary = implode(' · ', $parts);
    }

    $fleetsSummary = '';
    if (! empty($parsed['fleets'])) {
        $slots = $parsed['fleets']['fleet_slots'] ?? null;
        $used = $parsed['fleets']['fleets'] ?? null;
        if ($used !== null && $slots !== null) {
            $fleetsSummary = $used . '/' . $slots;
        }
    }

    $planetsSummary = '';
    $planetsDetail = [];
    if (! empty($parsed['planets'])) {
        $planetsSummary = count($parsed['planets']) . ' planetas';
        foreach ($parsed['planets'] as $planet) {
            if (! is_array($planet)) {
                continue;
            }
            $label = trim(($planet['name'] ?? '') . ' ' . ($planet['coords'] ?? ''));
            if ($label !== '') {
                $planetsDetail[] = $label;
            }
        }
    }

    $details = [
        'resources' => [
            'label' => 'Recursos',
            'summary' => $resourcesSummary,
            'value' => $parsed['resources'] ?? [],
        ],
        'fleets' => [
            'label' => 'Flotas',
            'summary' => $fleetsSummary,
            'value' => $parsed['fleets'] ?? [],
        ],
        'planets' => [
            'label' => 'Planetas',
            'summary' => $planetsSummary,
            'value' => $planetsDetail,
        ],
        'session' => [
            'label' => 'Sesion',
            'summary' => $parsed['meta_session'] ?? null,
            'value' => $parsed['meta_session'] ?? null,
        ],
        'universe' => [
            'label' => 'Universo',
            'summary' => $parsed['meta_universe'] ?? null,
            'value' => $parsed['meta_universe'] ?? null,
        ],
        'version' => [
            'label' => 'Version',
            'summary' => $parsed['meta_version'] ?? null,
            'value' => $parsed['meta_version'] ?? null,
        ],
        'timestamp' => [
            'label' => 'Timestamp',
            'summary' => $parsed['meta_timestamp'] ?? null,
            'value' => $parsed['meta_timestamp'] ?? null,
        ],
        'speed' => [
            'label' => 'Velocidad economia',
            'summary' => $parsed['meta_speed'] ?? null,
            'value' => $parsed['meta_speed'] ?? null,
        ],
        'fleet_peaceful' => [
            'label' => 'Flota paz',
            'summary' => $parsed['meta_fleet_peaceful'] ?? null,
            'value' => $parsed['meta_fleet_peaceful'] ?? null,
        ],
        'fleet_war' => [
            'label' => 'Flota guerra',
            'summary' => $parsed['meta_fleet_war'] ?? null,
            'value' => $parsed['meta_fleet_war'] ?? null,
        ],
        'fleet_holding' => [
            'label' => 'Flota defensiva',
            'summary' => $parsed['meta_fleet_holding'] ?? null,
            'value' => $parsed['meta_fleet_holding'] ?? null,
        ],
        'player_id' => [
            'label' => 'Player ID',
            'summary' => $parsed['player_id'] ?? null,
            'value' => $parsed['player_id'] ?? null,
        ],
        'player_name' => [
            'label' => 'Jugador',
            'summary' => $parsed['player_name'] ?? null,
            'value' => $parsed['player_name'] ?? null,
        ],
        'token' => [
            'label' => 'Token',
            'summary' => $parsed['token'] ?? null,
            'value' => $parsed['token'] ?? null,
        ],
        'meta' => [
            'label' => 'Meta tags',
            'summary' => ! empty($parsed['meta']) ? count($parsed['meta']) . ' campos' : null,
            'value' => $parsed['meta'] ?? [],
        ],
    ];

    Log::info('OGame login test response', [
        'status' => $response->status(),
        'login_detected' => $loginDetected,
        'body_length' => strlen($body),
        'snippet' => Str::limit($body, 1200, '...'),
        'player_id_found' => $parsed['player_id'] !== null,
        'player_name_found' => $parsed['player_name'] !== null,
        'token_found' => $parsed['token'] !== null,
    ]);

        if ($response->successful() && ! $parsed['is_lobby']) {
            AppSetting::query()->updateOrCreate(
                ['key' => $keyPrefix . '.cookie'],
                ['value' => encrypt($cookieHeader)]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => $keyPrefix . '.valid_at'],
                ['value' => now()->toDateTimeString()]
            );
            \Artisan::call('ogame:sync-overview', ['--force' => true]);
        } else {
            AppSetting::query()->where('key', $keyPrefix . '.valid_at')->delete();
        }

    if ($response->successful() && ! $parsed['is_lobby']) {
        OgameOverviewSnapshot::updateOrCreate(
            [
                'region' => $region,
                'universe_id' => $universe,
            ],
            [
                'player_id' => $parsed['player_id'],
                'source_url' => $testUrl,
                'status_code' => $response->status(),
                'is_lobby' => $parsed['is_lobby'],
                'fetched_at' => now(),
                'meta' => $parsed['meta'] ?? null,
                'resources' => $parsed['resources'] ?? null,
                'fleets' => $parsed['fleets'] ?? null,
                'planets' => $parsed['planets'] ?? null,
                'raw_html' => $body,
            ]
        );
    }

    $ok = $response->successful() && ! $loginDetected;
    $reason = null;
    $message = 'Sesion valida.';
    if (! $ok) {
        if ($parsed['is_lobby']) {
            $reason = 'lobby';
            $message = 'Cookie invalida o de dominio incorrecto. Copia la cookie desde el universo (s164-ar.ogame.gameforge.com).';
        } elseif ($loginDetected) {
            $reason = 'login';
            $message = 'El servidor devolvio pantalla de login. La cookie expiro o es incorrecta.';
        } else {
            $reason = 'error';
            $message = 'No se pudo validar la sesion con la cookie.';
        }
    }

    return back()->withInput()->with('login_test', [
        'ok' => $ok,
        'status' => $response->status(),
        'login_detected' => $loginDetected ? 'si' : 'no',
        'snippet' => $snippet !== '' ? $snippet : 'Sin contenido visible.',
        'details' => $details,
        'reason' => $reason,
        'message' => $message,
    ]);
})->name('login.test');

Route::get('/empire', function () {
    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();

    if (! OgameUniverse::isConfigured()) {
        return view('pages.empire', [
            'latest' => null,
            'resources' => [],
            'fleets' => [],
            'planets' => [],
            'meta' => [],
        ]);
    }

    $latest = OgameOverviewSnapshot::query()
        ->where('region', $region)
        ->where('universe_id', $universe)
        ->orderByDesc('fetched_at')
        ->first();

    return view('pages.empire', [
        'latest' => $latest,
        'resources' => $latest?->resources ?? [],
        'fleets' => $latest?->fleets ?? [],
        'planets' => $latest?->planets ?? [],
        'meta' => $latest?->meta ?? [],
        'galaxy' => $latest?->galaxy ?? [],
        'fleetdispatch' => $latest?->fleetdispatch ?? [],
        'eventbox' => $latest?->eventbox ?? [],
    ]);
})->name('empire');

Route::post('/empire/sync', function () {
    \Artisan::call('ogame:sync-overview', ['--force' => true]);
    return back()->with('status', 'Sync ejecutado.');
})->name('empire.sync');

Route::get('/settings', function () {
    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();
    $players = OgamePlayer::query()
        ->where('universe_id', $universe)
        ->orderBy('name')
        ->get(['ogame_id', 'name']);

    $selfKey = 'ogame.self_player_id.' . $region . '.' . $universe;
    $selfId = AppSetting::query()->where('key', $selfKey)->value('value');

    return view('pages.settings', [
        'players' => $players,
        'selfId' => $selfId,
    ]);
})->name('settings');

Route::post('/settings', function (Request $request) {
    $selected = (string) $request->input('universe', '');
    $selected = preg_replace('/\D/', '', $selected ?? '');
    $region = strtolower(trim((string) $request->input('region', '')));
    $selfId = trim((string) $request->input('self_player_id', ''));

    if ($selected === '' || $region === '') {
        return back()->with('status', 'Universo o region invalida.');
    }

    $allowedRegions = ['ar', 'br', 'mx', 'es', 'us', 'de', 'fr', 'it', 'pl', 'tr'];
    if (! in_array($region, $allowedRegions, true)) {
        return back()->with('status', 'Region invalida.');
    }

    $request->session()->put('universe', $selected);
    $request->session()->put('region', $region);
    OgameUniverse::storeUniverse($selected);
    OgameUniverse::storeRegion($region);

    $selfKey = 'ogame.self_player_id.' . $region . '.' . $selected;
    if ($selfId !== '') {
        $exists = OgamePlayer::query()
            ->where('universe_id', $selected)
            ->where('ogame_id', $selfId)
            ->exists();
        if (! $exists) {
            return back()->with('status', 'Jugador seleccionado no existe en el universo.');
        }
        AppSetting::query()->updateOrCreate(
            ['key' => $selfKey],
            ['value' => $selfId]
        );
    } else {
        AppSetting::query()->where('key', $selfKey)->delete();
    }

    return redirect()->route('home')->with('status', 'Configuracion actualizada.');
})->name('settings.save');

Route::get('/player', function (Request $request, OgameApiClient $apiClient) {
    if (! OgameUniverse::isConfigured()) {
        return redirect()->route('home')->with('status', 'Configura tu universo primero.');
    }

    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();
    $baseUrl = OgameUniverse::baseUrlFor($universe, $region);

    $settingKey = 'ogame.players_last_scan_at.' . $region . '.' . $universe;
    $alliancesSettingKey = 'ogame.alliances_last_scan_at.' . $region . '.' . $universe;
    $lastScanValue = AppSetting::query()->where('key', $settingKey)->value('value');
    $lastScan = $lastScanValue ? Carbon::parse($lastScanValue) : null;
    $needsSync = ! $lastScan || $lastScan->diffInHours(now()) >= 24;
    $alliancesLastScanValue = AppSetting::query()->where('key', $alliancesSettingKey)->value('value');
    $alliancesLastScan = $alliancesLastScanValue ? Carbon::parse($alliancesLastScanValue) : null;
    $alliancesNeedSync = ! $alliancesLastScan || $alliancesLastScan->diffInHours(now()) >= 24;

    if ($request->boolean('refresh') || $needsSync) {
        try {
            $rows = $apiClient->fetchPlayers($baseUrl);
            $now = now();
            $payload = [];
            foreach ($rows as $row) {
                if ($row['id'] === '') {
                    continue;
                }
                $payload[] = [
                    'universe_id' => $universe,
                    'ogame_id' => $row['id'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'alliance_id' => $row['alliance_id'],
                    'last_scan_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($payload)) {
                foreach (array_chunk($payload, 1000) as $chunk) {
                    OgamePlayer::query()->upsert(
                        $chunk,
                        ['universe_id', 'ogame_id'],
                        ['name', 'status', 'alliance_id', 'last_scan_at', 'updated_at']
                    );
                }
            }

            AppSetting::query()->updateOrCreate(
                ['key' => $settingKey],
                ['value' => $now->toIso8601String()]
            );
            $lastScan = $now;
        } catch (\Throwable $e) {
            return back()->with('status', 'Error al sincronizar jugadores: ' . $e->getMessage());
        }
    }

    if ($request->boolean('refresh') || $alliancesNeedSync) {
        try {
            $rows = $apiClient->fetchAlliances($baseUrl);
            $now = now();
            $payload = [];
            foreach ($rows as $row) {
                if ($row['id'] === '') {
                    continue;
                }
                $payload[] = [
                    'universe_id' => $universe,
                    'ogame_id' => $row['id'],
                    'name' => $row['name'],
                    'tag' => $row['tag'],
                    'last_scan_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($payload)) {
                foreach (array_chunk($payload, 1000) as $chunk) {
                    OgameAlliance::query()->upsert(
                        $chunk,
                        ['universe_id', 'ogame_id'],
                        ['name', 'tag', 'last_scan_at', 'updated_at']
                    );
                }
            }

            AppSetting::query()->updateOrCreate(
                ['key' => $alliancesSettingKey],
                ['value' => $now->toIso8601String()]
            );
        } catch (\Throwable $e) {
            return back()->with('status', 'Error al sincronizar alianzas: ' . $e->getMessage());
        }
    }

    $playersQuery = OgamePlayer::query()
        ->where('universe_id', $universe)
        ->orderBy('name')
        ->withCount('planets');

    $search = trim((string) $request->query('q', ''));
    $statusFilter = trim((string) $request->query('status', ''));
    $allianceFilter = trim((string) $request->query('alliance', ''));
    if ($search !== '') {
        $playersQuery->where('name', 'like', '%' . $search . '%');
    }
    if ($allianceFilter !== '') {
        $playersQuery->where('alliance_id', $allianceFilter);
    }
    if ($statusFilter !== '') {
        switch ($statusFilter) {
            case 'active':
                $playersQuery->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', '');
                });
                break;
            case 'inactive':
                $playersQuery->whereRaw('status LIKE ?', ['%I%']);
                break;
            case 'vacation':
                $playersQuery->whereRaw('LOWER(status) LIKE ?', ['%v%']);
                break;
            case 'banned':
                $playersQuery->whereRaw('LOWER(status) LIKE ?', ['%b%']);
                break;
            case 'admin':
                $playersQuery->whereRaw('LOWER(status) LIKE ?', ['%a%']);
                break;
            case 'suspended':
                $playersQuery->whereRaw('LOWER(status) LIKE ?', ['%s%']);
                break;
            case 'under_attack':
                $playersQuery->whereRaw('LOWER(status) LIKE ?', ['%u%']);
                break;
        }
    }

    $players = $playersQuery->get();
    $alliances = OgameAlliance::query()
        ->where('universe_id', $universe)
        ->get(['ogame_id', 'name', 'tag'])
        ->keyBy('ogame_id');

    $totalPlayers = $players->count();
    $inactiveCount = OgamePlayer::query()
        ->where('universe_id', $universe)
        ->whereRaw('LOWER(status) LIKE ?', ['%i%'])
        ->count();
    $vacationCount = OgamePlayer::query()
        ->where('universe_id', $universe)
        ->whereRaw('LOWER(status) LIKE ?', ['%v%'])
        ->count();
    $bannedCount = OgamePlayer::query()
        ->where('universe_id', $universe)
        ->whereRaw('LOWER(status) LIKE ?', ['%b%'])
        ->count();

    $selectedId = (string) $request->query('id', '');
    $selectedPlayer = null;
    $selectedMetrics = null;
    $selectedCoords = [];

    if ($selectedId !== '') {
        $selectedPlayer = OgamePlayer::query()
            ->where('universe_id', $universe)
            ->where('ogame_id', $selectedId)
            ->withCount('planets')
            ->first();

        if ($selectedPlayer) {
            $alliance = $alliances->get($selectedPlayer->alliance_id);
            $allianceLabel = $alliance ? trim(($alliance->tag ? $alliance->tag . ' - ' : '') . $alliance->name) : '—';
            $selectedCoords = OgamePlanet::query()
                ->where('universe_id', $universe)
                ->where('player_id', $selectedPlayer->ogame_id)
                ->orderBy('galaxy')
                ->orderBy('system')
                ->orderBy('position')
                ->get(['galaxy', 'system', 'position'])
                ->map(function ($planet) {
                    return $planet->galaxy . ':' . $planet->system . ':' . $planet->position;
                })
                ->values()
                ->all();
            $selectedMetrics = [
                'planets' => $selectedPlayer->planets_count ?? 0,
                'status' => $selectedPlayer->status ?: '—',
                'alliance' => $allianceLabel,
                'last_scan' => optional($selectedPlayer->last_scan_at)->toDateTimeString() ?: '—',
            ];
        }
    }

    return view('pages.players', [
        'universe' => $universe,
        'region' => $region,
        'baseUrl' => $baseUrl,
        'players' => $players,
        'alliances' => $alliances,
        'selectedPlayer' => $selectedPlayer,
        'selectedMetrics' => $selectedMetrics,
        'selectedCoords' => $selectedCoords,
        'totalPlayers' => $totalPlayers,
        'inactiveCount' => $inactiveCount,
        'vacationCount' => $vacationCount,
        'bannedCount' => $bannedCount,
        'lastScan' => $lastScan ? $lastScan->toDateTimeString() : null,
        'search' => $search,
        'statusFilter' => $statusFilter,
        'allianceFilter' => $allianceFilter,
    ]);
})->name('player');
Route::get('/planets', function (Request $request, OgameApiClient $apiClient) {
    if (! OgameUniverse::isConfigured()) {
        return redirect()->route('home')->with('status', 'Configura tu universo primero.');
    }

    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();
    $baseUrl = OgameUniverse::baseUrlFor($universe, $region);

    $settingKey = 'ogame.planets_last_scan_at.' . $region . '.' . $universe;
    $lastScanValue = AppSetting::query()->where('key', $settingKey)->value('value');
    $lastScan = $lastScanValue ? Carbon::parse($lastScanValue) : null;
    $needsSync = ! $lastScan || $lastScan->diffInDays(now()) >= 7;

    if ($request->boolean('refresh') || $needsSync) {
        try {
            $rows = $apiClient->fetchUniverse($baseUrl);
            $now = now();
            $payload = [];
            foreach ($rows as $row) {
                if ($row['id'] === '') {
                    continue;
                }
                $payload[] = [
                    'universe_id' => $universe,
                    'ogame_id' => $row['id'],
                    'name' => $row['name'],
                    'galaxy' => $row['galaxy'],
                    'system' => $row['system'],
                    'position' => $row['position'],
                    'player_id' => $row['player_id'],
                    'moon_id' => $row['moon_id'],
                    'moon_name' => $row['moon_name'],
                    'moon_size' => $row['moon_size'],
                    'last_scan_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($payload)) {
                foreach (array_chunk($payload, 1000) as $chunk) {
                    OgamePlanet::query()->upsert(
                        $chunk,
                        ['universe_id', 'ogame_id'],
                        ['name', 'galaxy', 'system', 'position', 'player_id', 'moon_id', 'moon_name', 'moon_size', 'last_scan_at', 'updated_at']
                    );
                }
            }

            AppSetting::query()->updateOrCreate(
                ['key' => $settingKey],
                ['value' => $now->toIso8601String()]
            );
            $lastScan = $now;
        } catch (\Throwable $e) {
            return back()->with('status', 'Error al sincronizar planetas: ' . $e->getMessage());
        }
    }

    $galaxy = (int) $request->query('galaxy', 0);
    $system = (int) $request->query('system', 0);
    $position = (int) $request->query('position', 0);
    $planetName = trim((string) $request->query('planet', ''));
    $playerName = trim((string) $request->query('player', ''));
    $onlyOccupied = $request->boolean('occupied');

    $planetsQuery = OgamePlanet::query()
        ->where('ogame_planets.universe_id', $universe)
        ->leftJoin('ogame_players', function ($join) {
            $join->on('ogame_planets.player_id', '=', 'ogame_players.ogame_id')
                ->on('ogame_planets.universe_id', '=', 'ogame_players.universe_id');
        })
        ->select([
            'ogame_planets.*',
            'ogame_players.name as player_name',
        ])
        ->orderBy('galaxy')
        ->orderBy('system')
        ->orderBy('position');

    if ($galaxy > 0) {
        $planetsQuery->where('ogame_planets.galaxy', $galaxy);
    }
    if ($system > 0) {
        $planetsQuery->where('ogame_planets.system', $system);
    }
    if ($position > 0) {
        $planetsQuery->where('ogame_planets.position', $position);
    }
    if ($planetName !== '') {
        $planetsQuery->where('ogame_planets.name', 'like', '%' . $planetName . '%');
    }
    if ($playerName !== '') {
        $planetsQuery->where('ogame_players.name', 'like', '%' . $playerName . '%');
    }
    if ($onlyOccupied) {
        $planetsQuery->whereNotNull('ogame_planets.player_id')
            ->where('ogame_planets.player_id', '<>', '');
    }

    $planets = $planetsQuery->paginate(50)->withQueryString();

    return view('pages.planets', [
        'universe' => $universe,
        'region' => $region,
        'baseUrl' => $baseUrl,
        'planets' => $planets,
        'lastScan' => $lastScan ? $lastScan->toDateTimeString() : null,
        'filters' => [
            'galaxy' => $galaxy,
            'system' => $system,
            'position' => $position,
            'planet' => $planetName,
            'player' => $playerName,
            'occupied' => $onlyOccupied,
        ],
    ]);
})->name('planets');

Route::get('/metrics', function (Request $request, OgameApiClient $apiClient) {
    if (! OgameUniverse::isConfigured()) {
        return redirect()->route('home')->with('status', 'Configura tu universo primero.');
    }

    $universe = OgameUniverse::getUniverse();
    $region = OgameUniverse::getRegion();
    $baseUrl = OgameUniverse::baseUrlFor($universe, $region);

    $category = (int) $request->query('category', 1);
    $type = (int) $request->query('type', 0);
    $page = (int) $request->query('page', 1);
    $category = in_array($category, [1, 2], true) ? $category : 1;
    $type = ($type >= 0 && $type <= 7) ? $type : 0;
    $page = ($page >= 1 && $page <= 400) ? $page : 1;

    $perPage = 50;
    $apiPageSize = 100;
    $apiPage = (int) ceil(($page * $perPage) / $apiPageSize);
    $apiOffset = (($page - 1) * $perPage) % $apiPageSize;

    $cacheKey = 'ogame.highscore.' . $region . '.' . $universe . '.' . $category . '.' . $apiPage;
    $cacheStampKey = $cacheKey . '.ts';

    $data = null;
    $error = null;

    $lastScanValue = AppSetting::query()->where('key', $cacheStampKey)->value('value');
    $lastScan = $lastScanValue ? Carbon::parse($lastScanValue) : null;
    $needsSync = ! $lastScan || $lastScan->diffInHours(now()) >= 6;

    if ($request->boolean('refresh') || $needsSync) {
        try {
            $rowsByType = [];
            for ($t = 0; $t <= 7; $t++) {
                $rowsByType[$t] = $apiClient->fetchHighscore($baseUrl, $category, $t, $apiPage);
            }

            $index = [];
            foreach ($rowsByType as $t => $rows) {
                foreach ($rows as $row) {
                    $id = $row['id'];
                    if ($id === '') {
                        continue;
                    }
                    if (! isset($index[$id])) {
                        $index[$id] = [
                            'id' => $id,
                            'position' => $row['position'],
                            'scores' => [],
                        ];
                    }
                    $index[$id]['scores'][$t] = $row['score'];
                }
            }

            $data = array_values($index);
            usort($data, function ($a, $b) {
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            });

            AppSetting::query()->updateOrCreate(
                ['key' => $cacheKey],
                ['value' => json_encode($data)]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => $cacheStampKey],
                ['value' => now()->toIso8601String()]
            );
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $cached = AppSetting::query()->where('key', $cacheKey)->value('value');
        if ($cached) {
            $data = json_decode($cached, true);
        }
    }

    $data = is_array($data) ? $data : [];
    $pageRows = array_slice($data, $apiOffset, $perPage);

    $summaryKey = 'ogame.highscore.summary.' . $region . '.' . $universe . '.' . $category . '.' . $type;
    $summaryStampKey = $summaryKey . '.ts';
    $summaryUniverse = null;
    $summaryScanValue = AppSetting::query()->where('key', $summaryStampKey)->value('value');
    $summaryScan = $summaryScanValue ? Carbon::parse($summaryScanValue) : null;
    $summaryNeedsSync = ! $summaryScan || $summaryScan->diffInHours(now()) >= 12;

    if ($request->boolean('refresh') || $summaryNeedsSync) {
        try {
            $scores = [];
            for ($p = 1; $p <= 200; $p++) {
                try {
                    $rows = $apiClient->fetchHighscore($baseUrl, $category, $type, $p);
                } catch (\Throwable $e) {
                    // If the API fails on higher pages, keep what we already have.
                    break;
                }
                if (count($rows) === 0) {
                    break;
                }
                foreach ($rows as $row) {
                    $scores[] = $row['score'] ?? 0;
                }
                if (count($rows) < 100) {
                    break;
                }
            }

            $summaryUniverse = [
                'count' => count($scores),
                'max' => $scores ? max($scores) : 0,
                'min' => $scores ? min($scores) : 0,
                'avg' => $scores ? (int) floor(array_sum($scores) / count($scores)) : 0,
                'median' => $scores ? (function ($vals) {
                    sort($vals);
                    $count = count($vals);
                    $mid = (int) floor($count / 2);
                    return $count % 2 ? $vals[$mid] : (int) floor(($vals[$mid - 1] + $vals[$mid]) / 2);
                })($scores) : 0,
            ];

            AppSetting::query()->updateOrCreate(
                ['key' => $summaryKey],
                ['value' => json_encode($summaryUniverse)]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => $summaryStampKey],
                ['value' => now()->toIso8601String()]
            );
        } catch (\Throwable $e) {
            // Keep summary empty on errors.
        }
    } else {
        $cachedSummary = AppSetting::query()->where('key', $summaryKey)->value('value');
        if ($cachedSummary) {
            $summaryUniverse = json_decode($cachedSummary, true);
        }
    }

    $summaryUniverse = is_array($summaryUniverse) ? $summaryUniverse : [
        'count' => 0,
        'max' => 0,
        'min' => 0,
        'avg' => 0,
        'median' => 0,
    ];

    $metrics = [];
    if ($category === 1 && count($pageRows) > 0) {
        $ratiosMilEco = [];
        $ratiosResEco = [];
        $totalScores = [];
        $top10Scores = [];

        foreach ($pageRows as $row) {
            $economy = $row['scores'][1] ?? 0;
            $military = $row['scores'][3] ?? 0;
            $research = $row['scores'][2] ?? 0;
            if ($economy > 0) {
                $ratiosMilEco[] = $military / $economy;
                $ratiosResEco[] = $research / $economy;
            }
            $totalScores[] = $row['scores'][0] ?? 0;
        }

        rsort($totalScores);
        $top10Scores = array_slice($totalScores, 0, 10);
        $sumTotal = array_sum($totalScores);
        $sumTop10 = array_sum($top10Scores);

        $metrics[] = ['label' => 'Ratio militar/economia promedio', 'value' => $ratiosMilEco ? number_format(array_sum($ratiosMilEco) / count($ratiosMilEco), 2) : '—'];
        $metrics[] = ['label' => 'Ratio investigacion/economia promedio', 'value' => $ratiosResEco ? number_format(array_sum($ratiosResEco) / count($ratiosResEco), 2) : '—'];
        $metrics[] = ['label' => 'Concentracion Top 10 (total)', 'value' => $sumTotal > 0 ? number_format(($sumTop10 / $sumTotal) * 100, 2) . '%' : '—'];
                $metrics[] = ['label' => 'Max / mediana', 'value' => $summaryUniverse['median'] > 0 ? number_format($summaryUniverse['max'] / $summaryUniverse['median'], 2) : '—'];
    }

    $nameMap = [];
    if ($category === 1) {
        $nameMap = OgamePlayer::query()
            ->where('universe_id', $universe)
            ->pluck('name', 'ogame_id')
            ->all();
    } else {
        $nameMap = OgameAlliance::query()
            ->where('universe_id', $universe)
            ->get(['ogame_id', 'name', 'tag'])
            ->mapWithKeys(function ($row) {
                $label = trim(($row->tag ? $row->tag . ' - ' : '') . $row->name);
                return [$row->ogame_id => $label];
            })
            ->all();
    }

    $typeLabels = [
        0 => 'Total',
        1 => 'Economia',
        2 => 'Investigacion',
        3 => 'Militar',
        4 => 'Militar construido',
        5 => 'Militar destruido',
        6 => 'Militar perdido',
        7 => 'Honor',
    ];

    $categoryLabels = [
        1 => 'Jugadores',
        2 => 'Alianzas',
    ];

    return view('pages.metrics', [
        'category' => $category,
        'type' => $type,
        'page' => $page,
        'data' => $pageRows,
        'summaryUniverse' => $summaryUniverse,
        'lastScan' => $lastScan ? $lastScan->toDateTimeString() : null,
        'typeLabels' => $typeLabels,
        'categoryLabels' => $categoryLabels,
        'error' => $error,
        'metrics' => $metrics,
        'perPage' => $perPage,
        'apiPage' => $apiPage,
        'nameMap' => $nameMap,
    ]);
})->name('metrics');
