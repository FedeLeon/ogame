@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 10 12 3l9 7v10h-6v-6H9v6H3z"/></svg>
            Imperio
        </h2>
        <div class="row" style="margin-top:8px; justify-content:flex-end;">
            <form method="post" action="{{ route('empire.sync') }}">
                @csrf
                <button class="btn success" type="submit">
                    <svg class="icon-refresh" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M3 12a9 9 0 0 1 15.55-6.36L21 3v6h-6l2.17-2.17A7 7 0 1 0 19 12h2a9 9 0 0 1-18 0Z"/>
                    </svg>
                    Sincronizar ahora
                </button>
            </form>
        </div>
        @if (! $latest)
            <p class="muted">No hay snapshots aun. Verifica la cookie y espera el sync.</p>
        @else
            <div class="kpis">
                <div class="kpi">
                    <div class="t">Ultima captura</div>
                    <div class="v mono">{{ $latest->fetched_at?->format('Y-m-d H:i:s') ?? '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Estado</div>
                    <div class="v mono">{{ $latest->status_code }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Lobby</div>
                    <div class="v mono">{{ $latest->is_lobby ? 'Si' : 'No' }}</div>
                </div>
            </div>
        @endif
    </div>

    @if ($latest)
        @php
            $resourceOrder = [
                'metal' => 'Metal',
                'crystal' => 'Cristal',
                'deuterium' => 'Deuterio',
                'energy' => 'Energia',
                'food' => 'Alimentacion',
                'darkmatter' => 'Materia oscura',
            ];
            $selectedPlanetId = (string) request('planet', '');
            $selectedPlanet = null;
            foreach ($planets as $planet) {
                if (! is_array($planet)) {
                    continue;
                }
                if ((string) ($planet['id'] ?? '') === $selectedPlanetId) {
                    $selectedPlanet = $planet;
                    break;
                }
            }
        @endphp
        <div class="resource-bar">
            @foreach ($resourceOrder as $key => $label)
                <div class="resource-tile">
                    <span class="resource-icon {{ $key }}"></span>
                    <div class="resource-meta">
                        <div class="resource-label">{{ $label }}</div>
                        <div class="resource-value mono">{{ number_format((int) ($resources[$key] ?? 0)) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card" style="margin-top:12px">
            <details class="details-panel" open>
                <summary>
                    <div>
                        <div class="panel-title">Planetas</div>
                        <div class="muted">Selecciona un planeta para ver su info</div>
                    </div>
                    <span class="details-arrow">▼</span>
                </summary>
                @if (count($planets))
                    <div class="planet-switcher">
                        @foreach ($planets as $planet)
                            @continue(! is_array($planet))
                            @php
                                $planetId = (string) ($planet['id'] ?? '');
                                $planetName = $planet['name'] ?? 'Planeta';
                                $planetCoords = $planet['coords'] ?? '';
                                $isActive = $planetId !== '' && $planetId === $selectedPlanetId;
                            @endphp
                            <a class="planet-chip {{ $isActive ? 'active' : '' }}"
                               href="{{ $planetId !== '' ? route('empire', ['planet' => $planetId]) : '#' }}">
                                <span class="planet-icon"></span>
                                <span class="planet-name">{{ $planetName }}</span>
                                <span class="planet-coords mono">[{{ $planetCoords }}]</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="planet-details">
                        @if ($selectedPlanet)
                            <div><strong>{{ $selectedPlanet['name'] ?? 'Planeta' }}</strong></div>
                            <div class="mono">ID {{ $selectedPlanet['id'] ?? '—' }} · [{{ $selectedPlanet['coords'] ?? '—' }}]</div>
                        @else
                            <span class="muted">Sin planeta seleccionado.</span>
                        @endif
                    </div>
                @else
                    <p class="muted">Sin datos de planetas.</p>
                @endif
            </details>
        </div>

        @php
            $slots = $fleetdispatch['slots'] ?? [];
            $slotItems = [
                [
                    'label' => 'Flotas',
                    'value' => (int) ($slots['fleets'] ?? 0),
                    'total' => (int) ($slots['fleet_slots'] ?? 0),
                    'hint' => 'Slots ocupados / totales',
                ],
                [
                    'label' => 'Expediciones',
                    'value' => (int) ($slots['expeditions'] ?? 0),
                    'total' => (int) ($slots['expedition_slots'] ?? 0),
                    'hint' => 'Slots de expedicion',
                ],
            ];
            $eventRecords = $eventbox['events'] ?? [];
            $eventPills = [
                ['label' => 'Propias', 'value' => $eventbox['friendly'] ?? 0, 'class' => 'pill-friendly'],
                ['label' => 'Neutrales', 'value' => $eventbox['neutral'] ?? 0, 'class' => 'pill-neutral'],
                ['label' => 'Enemigas', 'value' => $eventbox['hostile'] ?? 0, 'class' => 'pill-hostile'],
            ];
            $missionTypeLabels = [
                1 => 'Ataque',
                2 => 'Transporte',
                3 => 'Desplegar',
                4 => 'Mantener',
                5 => 'Espionaje',
                6 => 'Colonizar',
                7 => 'Reciclador',
                8 => 'Recolectar',
                9 => 'Destruir',
                10 => 'Bombardeo',
                11 => 'Expedición',
                12 => 'Expedición',
                13 => 'Ataque con misiles',
                14 => 'Desconocida',
                15 => 'Expedición',
            ];
            $missionTypeIcons = [
                1 => '⚔️',
                2 => '🚚',
                3 => '📦',
                4 => '🛡️',
                5 => '🛰️',
                6 => '🌍',
                7 => '🧹',
                8 => '🛠️',
                9 => '💥',
                10 => '🔥',
                11 => '🛰️',
                12 => '🛰️',
                15 => '🚀',
            ];
        @endphp

        <div class="card movements-card" style="margin-top:12px">
            <details class="movements-panel" open>
                <summary>
                    <div>
                        <div class="movements-title">Movimientos de flota</div>
                        <div class="muted">Slots, eventos y despachos resumidos</div>
                    </div>
                    <div class="pill-row">
                        @foreach ($eventPills as $pill)
                            <span class="pill {{ $pill['class'] }}">{{ $pill['label'] }} {{ $pill['value'] }}</span>
                        @endforeach
                    </div>
                    <span class="details-arrow">▼</span>
                </summary>
                <div class="movements-content">
                    <div class="slots-grid">
                        @foreach ($slotItems as $slot)
                            <div class="slot-card">
                                <div class="slot-label">{{ $slot['label'] }}</div>
                                <div class="slot-value">{{ $slot['value'] }} / {{ $slot['total'] }}</div>
                                <div class="slot-hint">{{ $slot['hint'] }}</div>
                            </div>
                        @endforeach
                        <div class="slot-card">
                            <div class="slot-label">Flotas activas</div>
                            <div class="slot-value">{{ $fleetdispatch['active_flights'] ?? 0 }}</div>
                            <div class="slot-hint">Eventos detectados</div>
                        </div>
                    </div>
                    <div class="events-section">
                        <div class="events-header">
                            <span>Flotas en vuelo</span>
                            <span class="muted">{{ count($eventRecords) }} eventos</span>
                        </div>
                        @if (count($eventRecords))
                            <table class="events-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Misión</th>
                                        <th>Destino</th>
                                        <th>Llegada</th>
                                        <th>Retorno</th>
                                        <th>Naves</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($eventRecords as $event)
                                        @php
                                            $missionType = (int) ($event['mission_type'] ?? 0);
                                            $missionLabel = $missionTypeLabels[$missionType] ?? ($event['mission'] ?? '—');
                                            $missionIcon = $missionTypeIcons[$missionType] ?? '🚀';
                                            $returning = in_array($event['return'], [true, 'true', 1, '1'], true);
                                        @endphp
                                        <tr>
                                            <td class="mission-icon-cell">
                                                <span class="mission-icon">{{ $missionIcon }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $missionLabel }}</strong>
                                                @if (! empty($event['mission_title']))
                                                    <div class="muted small">{{ $event['mission_title'] }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $event['dest_coords'] ?? '—' }}</div>
                                                <div class="muted small">{{ $event['dest_name'] ?? '' }}</div>
                                            </td>
                                            <td>{{ $event['arrival_time_text'] ?? $event['arrival_time'] ?? '—' }}</td>
                                            <td>{{ $returning ? 'Sí' : 'No' }}</td>
                                            <td class="mono">{{ $event['ships_total'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="muted">No hay flotas en vuelo actualmente.</p>
                        @endif
                    </div>
                </div>
            </details>
        </div>


    @endif
@endsection
