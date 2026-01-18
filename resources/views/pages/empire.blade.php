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

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8Zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6Z"/><path fill="currentColor" d="M2 13c4-2 10-3 20-1v2c-10-2-16-1-20 1z"/></svg>
                Planetas
            </h3>
            @if (count($planets))
                <ul class="coord-list mono">
                    @foreach ($planets as $planet)
                        <li>{{ is_array($planet) ? implode(' ', $planet) : $planet }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">Sin datos de planetas.</p>
            @endif
        </div>

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
                Galaxia
            </h3>
            @if (count($galaxy))
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($galaxy as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td class="mono">{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">Sin datos de galaxia.</p>
            @endif
        </div>

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 7h16v2H4zm0 4h16v2H4zm0 4h16v2H4z"/></svg>
                Meta etiquetas
            </h3>
            @if (count($meta))
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meta as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td class="mono">{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">Sin meta tags.</p>
            @endif
        </div>
    @endif
@endsection
