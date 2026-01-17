@php
    $tabs = [
        'home' => ['label' => 'Inicio', 'route' => 'home'],
        'player' => ['label' => 'Jugador (stats)', 'route' => 'player'],
        'planets' => ['label' => 'Planetas (buscar)', 'route' => 'planets'],
        'inactives' => ['label' => 'Inactivos cerca', 'route' => 'inactives'],
        'distance' => ['label' => 'Distancia', 'route' => 'distance'],
        'api' => ['label' => 'API raw', 'route' => 'api'],
    ];
    $current = $current ?? request()->route()?->getName();
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
<div class="wrap">
    <nav>
        @foreach ($tabs as $tab)
            <a class="navlink {{ $tab['route'] === $current ? 'active' : '' }}" href="{{ route($tab['route']) }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
        <a class="navlink {{ $current === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">
            Configuracion
        </a>
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
