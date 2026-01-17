@php
    $universes = config('ogame.universes', []);
    $selectedUniverse = session('universe', config('ogame.default_universe'));
    $selectedUniverseData = collect($universes)->firstWhere('id', $selectedUniverse);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Bienvenido</h2>
        <p class="muted">
            Este tool usa solo la API pública. Si querés buscar planetas, vas a necesitar cargar
            <span class="mono">universe.xml</span> (pesado).
        </p>
        <div class="kpis">
            <div class="kpi">
                <div class="t">Universo</div>
                <div class="v mono">{{ $selectedUniverseData['label'] ?? $selectedUniverse }}</div>
            </div>
            <div class="kpi">
                <div class="t">Dominio</div>
                <div class="v mono">{{ $selectedUniverseData['domain'] ?? '—' }}</div>
            </div>
            <div class="kpi">
                <div class="t">Modo</div>
                <div class="v mono">Lectura</div>
            </div>
            <div class="kpi">
                <div class="t">Stack</div>
                <div class="v mono">Laravel 10</div>
            </div>
        </div>
    </div>

    <div class="grid2" style="margin-top:12px">
        <div class="card">
            <h3>Herramientas incluidas</h3>
            <ul class="muted">
                <li>Stats de jugadores via <span class="mono">highscore.xml</span></li>
                <li>Búsqueda de planetas por coords/jugador/nombre via <span class="mono">universe.xml</span></li>
                <li>Inactivos cerca (misma galaxia) por status en <span class="mono">players.xml</span></li>
                <li>Calculadora de distancia (coords)</li>
            </ul>
        </div>
        <div class="card">
            <h3>Notas</h3>
            <p class="muted">
                Todo es server-side. Sin JavaScript. Para “stats” el ranking está paginado, así que la búsqueda puede tardar.
            </p>
        </div>
    </div>
@endsection
