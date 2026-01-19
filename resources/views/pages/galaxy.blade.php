@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8Z"/><path fill="currentColor" d="M2 13c4-2 10-3 20-1v2c-10-2-16-1-20 1z"/></svg>
            Galaxia
        </h2>
        <div class="galaxy-nav">
            <a class="btn secondary {{ $prevSystem ? '' : 'disabled' }}" href="{{ $prevSystem ? route('galaxy', ['galaxy' => $galaxyId, 'system' => $prevSystem]) : '#' }}" aria-disabled="{{ $prevSystem ? 'false' : 'true' }}">
                ◀ Sistema
            </a>
            <div class="galaxy-nav-title">
                <div class="mono">Galaxia {{ $galaxyId }} · Sistema {{ $systemId }}</div>
                <div class="muted">Mi planeta: {{ $ownPlanet }} [{{ $ownCoords }}]</div>
            </div>
            <a class="btn secondary {{ $nextSystem ? '' : 'disabled' }}" href="{{ $nextSystem ? route('galaxy', ['galaxy' => $galaxyId, 'system' => $nextSystem]) : '#' }}" aria-disabled="{{ $nextSystem ? 'false' : 'true' }}">
                Sistema ▶
            </a>
        </div>
    </div>

    <div class="card galaxy-table-card" style="margin-top:12px">
        <div class="galaxy-table-header">
            <span>Posición</span>
            <span>Planeta</span>
            <span>Escombros</span>
            <span>Jugador</span>
            <span>Estado</span>
        </div>
        <div class="galaxy-table-body">
            @foreach ($rows as $row)
                @php
                    $statusList = $row['status'] ?? [];
                    $isOwn = $ownPos && $ownPos === ($row['position'] ?? null);
                    $debrisMetal = (int) ($row['debris_metal'] ?? 0);
                    $debrisCrystal = (int) ($row['debris_crystal'] ?? 0);
                    $hasDebris = ($debrisMetal + $debrisCrystal) > 0;
                @endphp
                <div class="galaxy-row {{ $isOwn ? 'own' : '' }}">
                    <div class="galaxy-cell position mono">{{ $row['position'] }}</div>
                    <div class="galaxy-cell planet">
                        <span class="planet-icon"></span>
                        <span>{{ $row['planet_name'] ?? 'Vacío' }}</span>
                    </div>
                    <div class="galaxy-cell debris">
                        @if ($hasDebris)
                            <span class="debris-icon"></span>
                            <span class="mono">M {{ number_format($debrisMetal) }}</span>
                            <span class="mono">C {{ number_format($debrisCrystal) }}</span>
                        @else
                            <span class="muted">Sin escombros</span>
                        @endif
                    </div>
                    <div class="galaxy-cell player">
                        <span>{{ $row['player_name'] ?? 'Libre' }}</span>
                    </div>
                    <div class="galaxy-cell status">
                        @if (! empty($row['activity']))
                            <span class="pill pill-neutral">{{ $row['activity'] }}</span>
                        @endif
                        @forelse ($statusList as $status)
                            <span class="pill pill-{{ $status }}">{{ $statusLabels[$status] ?? $status }}</span>
                        @empty
                            <span class="muted">Normal</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
