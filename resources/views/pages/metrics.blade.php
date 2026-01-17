@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 19h16v2H4zM6 10h3v7H6zm5-4h3v11h-3zm5 2h3v9h-3z"/></svg>
            Metricas y Highscore
        </h2>
        <form method="get" action="{{ route('metrics') }}" class="row" style="margin-top:10px">
            <label>
                Categoria
                <select name="category">
                    @foreach ($categoryLabels as $key => $label)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Tipo
                <select name="type">
                    @foreach ($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn secondary" type="submit">Ver</button>
        </form>

        <div class="muted" style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span>Ultima sincronizacion: <span class="mono">{{ $lastScan ?? '—' }}</span></span>
            <span>Pagina API: <span class="mono">{{ $apiPage }}</span></span>
        </div>
        <div style="margin-top:10px">
            <a class="btn success" href="{{ route('metrics', ['category' => $category, 'type' => $type, 'page' => $page, 'refresh' => 1]) }}">
                <svg class="icon-refresh" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M3 12a9 9 0 0 1 15.55-6.36L21 3v6h-6l2.17-2.17A7 7 0 1 0 19 12h2a9 9 0 0 1-18 0Z"/>
                </svg>
                Refrescar
            </a>
        </div>

        @if ($error)
            <div class="alert err" style="margin-top:10px">Error: {{ $error }}</div>
        @endif
    </div>

    <div class="grid2" style="margin-top:12px">
        <div class="card">
            <h3>Resumen del universo ({{ $typeLabels[$type] }})</h3>
            <div class="kpis">
                <div class="kpi">
                    <div class="t">Registros</div>
                    <div class="v mono">{{ $summaryUniverse['count'] }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Maximo</div>
                    <div class="v mono">{{ number_format($summaryUniverse['max']) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Minimo</div>
                    <div class="v mono">{{ number_format($summaryUniverse['min']) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Promedio</div>
                    <div class="v mono">{{ number_format($summaryUniverse['avg']) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Mediana</div>
                    <div class="v mono">{{ number_format($summaryUniverse['median']) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Metricas sugeridas</h3>
            @if (count($metrics))
                <table>
                    <thead>
                        <tr>
                            <th>Metrica</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metrics as $metric)
                            <tr>
                                <td>{{ $metric['label'] }}</td>
                                <td class="mono">{{ $metric['value'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">Disponible para categoria Jugadores.</p>
            @endif
        </div>
    </div>

    <div class="card" style="margin-top:12px">
        <div class="card-header">
            <h3>Highscore (pagina {{ $page }})</h3>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <a class="btn secondary" href="{{ route('metrics', ['category' => $category, 'type' => $type, 'page' => max(1, $page - 1)]) }}">
                    ◀ Anterior
                </a>
                <span class="mono">Pagina {{ $page }}</span>
                <a class="btn secondary" href="{{ route('metrics', ['category' => $category, 'type' => $type, 'page' => min(400, $page + 1)]) }}">
                    Siguiente ▶
                </a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Total</th>
                    <th>Economia</th>
                    <th>Investigacion</th>
                    <th>Militar</th>
                    <th>Militar construido</th>
                    <th>Militar destruido</th>
                    <th>Militar perdido</th>
                    <th>Honor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $row)
                    <tr>
                        <td class="mono">{{ $row['position'] }}</td>
                        <td class="mono">
                            {{ $nameMap[$row['id']] ?? $row['id'] }}
                        </td>
                        <td class="mono">{{ number_format($row['scores'][0] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][1] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][2] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][3] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][4] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][5] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][6] ?? 0) }}</td>
                        <td class="mono">{{ number_format($row['scores'][7] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="muted">Sin datos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
