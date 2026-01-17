<?php

use App\Support\OgameUniverse;
use App\Models\AppSetting;
use App\Models\OgameAlliance;
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

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::view('/login', 'pages.login')->name('login');
Route::post('/login', function () {
    return back()->with('status', 'Login pendiente de implementar.');
})->name('login.submit');

Route::get('/settings', function () {
    return view('pages.settings');
})->name('settings');

Route::post('/settings', function (Request $request) {
    $selected = (string) $request->input('universe', '');
    $selected = preg_replace('/\D/', '', $selected ?? '');
    $region = strtolower(trim((string) $request->input('region', '')));

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

    if ($selectedId !== '') {
        $selectedPlayer = OgamePlayer::query()
            ->where('universe_id', $universe)
            ->where('ogame_id', $selectedId)
            ->withCount('planets')
            ->first();

        if ($selectedPlayer) {
            $alliance = $alliances->get($selectedPlayer->alliance_id);
            $allianceLabel = $alliance ? trim(($alliance->tag ? $alliance->tag . ' - ' : '') . $alliance->name) : '—';
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
Route::view('/planets', 'pages.coming-soon', ['title' => 'Planetas (buscar)'])->name('planets');
Route::view('/inactives', 'pages.coming-soon', ['title' => 'Inactivos cerca'])->name('inactives');
Route::view('/distance', 'pages.coming-soon', ['title' => 'Distancia'])->name('distance');
Route::view('/api', 'pages.coming-soon', ['title' => 'API raw'])->name('api');
