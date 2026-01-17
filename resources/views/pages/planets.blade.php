@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Escaneo de planetas</h2>
        <p class="muted">
            Universo {{ $universe }} · Region {{ strtoupper($region) }} · Base {{ $baseUrl }}
        </p>
        <div class="muted" style="margin-top:10px">
            Ultima sincronizacion: <span class="mono">{{ $lastScan ?? '—' }}</span>
            · <a class="navlink" href="{{ route('planets', ['refresh' => 1]) }}">Actualizar ahora</a>
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
                    <th>Jugador</th>
                    <th>ID jugador</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($planets as $planet)
                    <tr>
                        <td class="mono">{{ $planet->galaxy }}:{{ $planet->system }}:{{ $planet->position }}</td>
                        <td>{{ $planet->name ?: '—' }}</td>
                        <td>{{ $planet->player_name ?: '—' }}</td>
                        <td class="mono">{{ $planet->player_id ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">Sin resultados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px">
            {{ $planets->links() }}
        </div>
    </div>
@endsection
