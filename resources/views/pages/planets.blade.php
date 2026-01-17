@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8Zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6Z"/><path fill="currentColor" d="M2 13c4-2 10-3 20-1v2c-10-2-16-1-20 1z"/></svg>
            Escaneo de planetas
        </h2>
        <div class="muted" style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span>Ultima sincronizacion: <span class="mono">{{ $lastScan ?? '—' }}</span></span>
            <a class="btn success" href="{{ route('planets', ['refresh' => 1]) }}">
                <svg class="icon-refresh" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M3 12a9 9 0 0 1 15.55-6.36L21 3v6h-6l2.17-2.17A7 7 0 1 0 19 12h2a9 9 0 0 1-18 0Z"/>
                </svg>
                Refrescar
            </a>
        </div>
    </div>

    <div class="card" style="margin-top:12px">
        <h3>Filtros</h3>
        <form method="get" action="{{ route('planets') }}" class="row" style="margin-top:10px">
            <label>
                Galaxia
                <input type="number" name="galaxy" value="{{ $filters['galaxy'] ?: '' }}" min="1" />
            </label>
            <label>
                Sistema
                <input type="number" name="system" value="{{ $filters['system'] ?: '' }}" min="1" />
            </label>
            <label>
                Posicion
                <input type="number" name="position" value="{{ $filters['position'] ?: '' }}" min="1" />
            </label>
            <label>
                Planeta
                <input type="text" name="planet" value="{{ $filters['planet'] }}" placeholder="Nombre planeta" />
            </label>
            <label>
                Jugador
                <input type="text" name="player" value="{{ $filters['player'] }}" placeholder="Nombre jugador" />
            </label>
            <label>
                <input type="checkbox" name="occupied" value="1" @checked($filters['occupied']) />
                Solo ocupados
            </label>
            <button class="btn secondary" type="submit">Buscar</button>
            @if ($filters['galaxy'] || $filters['system'] || $filters['position'] || $filters['planet'] !== '' || $filters['player'] !== '' || $filters['occupied'])
                <a class="btn secondary" href="{{ route('planets') }}">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="card" style="margin-top:12px">
        <h3>Resultados</h3>
        <table>
            <thead>
                <tr>
                    <th>Coords</th>
                    <th>Planeta</th>
                    <th>Luna</th>
                    <th>Jugador</th>
                    <th>ID jugador</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($planets as $planet)
                    <tr>
                        <td class="mono">{{ $planet->galaxy }}:{{ $planet->system }}:{{ $planet->position }}</td>
                        <td>{{ $planet->name ?: '—' }}</td>
                        <td class="mono">
                            @if ($planet->moon_id)
                                {{ $planet->moon_name ?: 'Luna' }} ({{ $planet->moon_size }})
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $planet->player_name ?: '—' }}</td>
                        <td class="mono">{{ $planet->player_id ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">Sin resultados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px">
            {{ $planets->links() }}
        </div>
    </div>
@endsection
