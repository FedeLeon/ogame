@extends('layouts.app')

@php
    $statusMap = [
        'v' => ['label' => 'Vacaciones', 'class' => 'vacation'],
        'i' => ['label' => 'Inactivo', 'class' => 'inactive'],
        'I' => ['label' => 'Inactivo largo', 'class' => 'inactive-long'],
        'b' => ['label' => 'Baneado', 'class' => 'banned'],
        'a' => ['label' => 'Admin', 'class' => 'admin'],
        's' => ['label' => 'Suspendido', 'class' => 'suspended'],
        'u' => ['label' => 'Bajo ataque', 'class' => 'under-attack'],
    ];

    $statusBadges = function (?string $status) use ($statusMap) {
        $status = $status ? trim($status) : '';
        if ($status === '') {
            return [
                ['label' => 'Activo', 'class' => 'active'],
            ];
        }

        $badges = [];
        foreach (str_split($status) as $code) {
            if (isset($statusMap[$code])) {
                $badges[] = $statusMap[$code];
            }
        }

        return count($badges) ? $badges : [['label' => $status, 'class' => 'unknown']];
    };
@endphp

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4Z"/></svg>
            Jugadores del universo {{ $universe }}
        </h2>
        <div class="kpis">
            <div class="kpi">
                <div class="t">Total</div>
                <div class="v mono">{{ $totalPlayers }}</div>
            </div>
            <div class="kpi">
                <div class="t">Inactivos</div>
                <div class="v mono">{{ $inactiveCount }}</div>
            </div>
            <div class="kpi">
                <div class="t">Vacaciones</div>
                <div class="v mono">{{ $vacationCount }}</div>
            </div>
            <div class="kpi">
                <div class="t">Baneados</div>
                <div class="v mono">{{ $bannedCount }}</div>
            </div>
        </div>
        <div class="muted" style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span>Ultima sincronizacion: <span class="mono">{{ $lastScan ?? '—' }}</span></span>
            <a class="btn success" href="{{ route('player', ['refresh' => 1]) }}">
                <svg class="icon-refresh" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M3 12a9 9 0 0 1 15.55-6.36L21 3v6h-6l2.17-2.17A7 7 0 1 0 19 12h2a9 9 0 0 1-18 0Z"/>
                </svg>
                Refrescar
            </a>
        </div>
    </div>

    <div class="grid2" style="margin-top:12px">
        <div class="card">
            <h3>Listado completo</h3>
            <form method="get" action="{{ route('player') }}" class="row" style="margin:10px 0">
                <label>
                    Buscar por nombre
                    <input type="text" name="q" value="{{ $search }}" placeholder="Nombre de jugador" />
                </label>
                <label>
                    Estado
                    <select name="status">
                        <option value="" @selected($statusFilter === '')>Todos</option>
                        <option value="active" @selected($statusFilter === 'active')>Activo</option>
                        <option value="inactive" @selected($statusFilter === 'inactive')>Inactivos</option>
                        <option value="vacation" @selected($statusFilter === 'vacation')>Vacaciones</option>
                        <option value="banned" @selected($statusFilter === 'banned')>Baneado</option>
                        <option value="admin" @selected($statusFilter === 'admin')>Admin</option>
                        <option value="suspended" @selected($statusFilter === 'suspended')>Suspendido</option>
                        <option value="under_attack" @selected($statusFilter === 'under_attack')>Bajo ataque</option>
                    </select>
                </label>
                <label>
                    Alianza
                    <select name="alliance">
                        <option value="" @selected($allianceFilter === '')>Todas</option>
                        @foreach ($alliances as $alliance)
                            @php
                                $label = trim(($alliance->tag ? $alliance->tag . ' - ' : '') . $alliance->name);
                            @endphp
                            <option value="{{ $alliance->ogame_id }}" @selected($allianceFilter === $alliance->ogame_id)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <button class="btn secondary" type="submit">Buscar</button>
                @if ($search !== '' || $statusFilter !== '' || $allianceFilter !== '')
                    <a class="btn secondary" href="{{ route('player') }}">Limpiar</a>
                @endif
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Jugador</th>
                        <th>Status</th>
                        <th>Alianza</th>
                        <th>Planetas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($players as $player)
                        @php
                            $alliance = $alliances->get($player->alliance_id);
                            $allianceLabel = $alliance ? trim(($alliance->tag ? $alliance->tag . ' - ' : '') . $alliance->name) : '—';
                        @endphp
                        <tr>
                            <td>
                                <a class="navlink" href="{{ route('player', ['id' => $player->ogame_id, 'q' => $search ?: null, 'status' => $statusFilter ?: null, 'alliance' => $allianceFilter ?: null]) }}">
                                    {{ $player->name }}
                                </a>
                            </td>
                            <td>
                                @foreach ($statusBadges($player->status) as $badge)
                                    <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                @endforeach
                            </td>
                            <td class="mono">{{ $allianceLabel }}</td>
                            <td class="mono">{{ $player->planets_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">Sin datos. Proba actualizar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Detalle jugador</h3>
            @if ($selectedPlayer)
                <p class="mono"><strong>{{ $selectedPlayer->name }}</strong> (ID {{ $selectedPlayer->ogame_id }})</p>
                <div class="kpis">
                    <div class="kpi">
                        <div class="t">Planetas</div>
                        <div class="v mono">{{ $selectedMetrics['planets'] }}</div>
                    </div>
                    <div class="kpi">
                        <div class="t">Coordenadas</div>
                        <div class="v">
                            @if (count($selectedCoords))
                                <ul class="coord-list mono">
                                    @foreach ($selectedCoords as $coord)
                                        <li>{{ $coord }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="mono">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="kpi">
                        <div class="t">Status</div>
                        <div class="v">
                            @foreach ($statusBadges($selectedPlayer->status) as $badge)
                                <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="kpi">
                        <div class="t">Alianza</div>
                        <div class="v mono">{{ $selectedMetrics['alliance'] }}</div>
                    </div>
                    <div class="kpi">
                        <div class="t">Ultimo scan</div>
                        <div class="v mono">{{ $selectedMetrics['last_scan'] }}</div>
                    </div>
                </div>
            @else
                <p class="muted">Selecciona un jugador del listado para ver sus metricas.</p>
            @endif
        </div>
    </div>
@endsection
