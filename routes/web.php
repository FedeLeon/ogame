<?php

use App\Support\OgameUniverse;
use App\Models\AppSetting;
use App\Models\OgameAlliance;
use App\Models\OgamePlanet;
use App\Models\OgamePlayer;
use App\Services\OgameApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;

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
    }

    return view('pages.home', [
        'serverData' => $serverData,
        'serverError' => $serverError,
        'selfPlayer' => $selfPlayer,
        'selfPlanets' => $selfPlanets,
    ]);
})->name('home');

Route::view('/login', 'pages.login')->name('login');
Route::post('/login', function () {
    return back()->with('status', 'Login pendiente de implementar.');
})->name('login.submit');

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
