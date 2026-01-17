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
        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 7h16v10H4z"/><path fill="currentColor" d="M7 4h10v2H7zm0 14h10v2H7z"/></svg>
                Recursos
            </h3>
            @if (count($resources))
                <table>
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resources as $key => $value)
                            <tr>
                                <td>{{ ucfirst($key) }}</td>
                                <td class="mono">{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">Sin datos de recursos.</p>
            @endif
        </div>

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 12l9-7 9 7-9 7z"/><path fill="currentColor" d="M12 5v14"/></svg>
                Flotas
            </h3>
            @if (count($fleets))
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fleets as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td class="mono">{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">Sin datos de flotas.</p>
            @endif
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
                Galaxy
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
                <p class="muted">Sin datos de galaxy.</p>
            @endif
        </div>

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 4h14v4H5zm-2 6h18v10H3z"/></svg>
                Fleetdispatch
            </h3>
            @if (count($fleetdispatch))
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fleetdispatch as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td class="mono">{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">Sin datos de fleetdispatch.</p>
            @endif
        </div>

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5h16v14H4z"/><path fill="currentColor" d="M7 8h10v2H7zm0 4h10v2H7z"/></svg>
                Eventbox
            </h3>
            @if (count($eventbox))
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eventbox as $key => $value)
                            @if ($key !== 'events')
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td class="mono">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @if (! empty($eventbox['events']))
                    <div style="margin-top:12px">
                        <h4>Flotas en vuelo</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Mision</th>
                                    <th>Retorno</th>
                                    <th>Llega</th>
                                    <th>Origen</th>
                                    <th>Destino</th>
                                    <th>Naves</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($eventbox['events'] as $event)
                                    <tr>
                                        <td>{{ $event['type'] ?? '—' }}</td>
                                        <td>{{ $event['mission'] ?? $event['mission_title'] ?? $event['mission_type'] ?? '—' }}</td>
                                        <td class="mono">{{ $event['return'] ?? '—' }}</td>
                                        <td class="mono">{{ $event['arrival_time_text'] ?? $event['arrival_time'] ?? $event['time'] ?? '—' }}</td>
                                        <td class="mono">{{ $event['origin_coords'] ?? '—' }}</td>
                                        <td class="mono">{{ $event['dest_coords'] ?? '—' }}</td>
                                        <td class="mono">{{ $event['ships_total'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <p class="muted">Sin datos de eventbox.</p>
            @endif
        </div>

        <div class="card" style="margin-top:12px">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 7h16v2H4zm0 4h16v2H4zm0 4h16v2H4z"/></svg>
                Meta tags
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
