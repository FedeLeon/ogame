@php
    $tabs = [
        'home' => ['label' => 'Inicio', 'route' => 'home'],
        'player' => ['label' => 'Jugador (stats)', 'route' => 'player'],
        'planets' => ['label' => 'Planetas (buscar)', 'route' => 'planets'],
        'inactives' => ['label' => 'Inactivos cerca', 'route' => 'inactives'],
        'distance' => ['label' => 'Distancia', 'route' => 'distance'],
        'api' => ['label' => 'API raw', 'route' => 'api'],
        'login' => ['label' => 'Login', 'route' => 'login'],
    ];
    $current = $current ?? request()->route()?->getName();
    $universes = config('ogame.universes', []);
    $selectedUniverse = session('universe', config('ogame.default_universe'));
    $selectedUniverseData = collect($universes)->firstWhere('id', $selectedUniverse);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>OGame AR — Tool</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header class="header">
    <h1>OGame AR — Universo {{ $selectedUniverseData['id'] ?? $selectedUniverse }} — Tool (API pública) · Laravel</h1>
    <div class="sub">
        Solo lectura de endpoints públicos <span class="mono">/api/*.xml</span>. Sin login automático, sin automatización de acciones.
    </div>
    <div class="header-tools">
        <span class="label">Universo:</span>
        <form method="post" action="{{ route('universe.select') }}" class="row">
            @csrf
            <select name="universe" onchange="this.form.submit()">
                @foreach ($universes as $universe)
                    <option value="{{ $universe['id'] }}" @selected($universe['id'] === $selectedUniverse)>
                        {{ $universe['label'] }}
                    </option>
                @endforeach
            </select>
            <noscript>
                <button class="btn secondary" type="submit">Cambiar</button>
            </noscript>
        </form>
    </div>
</header>

<div class="wrap">
    <nav>
        @foreach ($tabs as $tab)
            <a class="navlink {{ $tab['route'] === $current ? 'active' : '' }}" href="{{ route($tab['route']) }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <main>
        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</div>

<footer class="footer">
    Cache local en archivos (para no saturar la API). Versión Laravel 10.
</footer>
</body>
</html>
