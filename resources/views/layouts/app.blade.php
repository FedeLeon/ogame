@php
    $tabs = [
        'home' => ['label' => 'Inicio', 'route' => 'home', 'icon' => 'home'],
        'empire' => ['label' => 'Imperio', 'route' => 'empire', 'icon' => 'empire'],
        'player' => ['label' => 'Jugadores', 'route' => 'player', 'icon' => 'user'],
        'galaxy' => ['label' => 'Galaxia', 'route' => 'galaxy', 'icon' => 'galaxy'],
        'planets' => ['label' => 'Planetas', 'route' => 'planets', 'icon' => 'planet'],
        'metrics' => ['label' => 'Metricas', 'route' => 'metrics', 'icon' => 'metrics'],
    ];
    $current = $current ?? request()->route()?->getName();

    $icons = [
        'home' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3 3 10v10h6v-6h6v6h6V10z"/></svg>',
        'user' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4Z"/></svg>',
        'planet' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8Zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6Z"/><path fill="currentColor" d="M2 13c4-2 10-3 20-1v2c-10-2-16-1-20 1z"/></svg>',
        'galaxy' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8Z"/><path fill="currentColor" d="M2 13c4-2 10-3 20-1v2c-10-2-16-1-20 1z"/></svg>',
        'idle' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8Zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6Z"/><path fill="currentColor" d="M11 7h2v6h-2zm0 8h2v2h-2z"/></svg>',
        'distance' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 12a2 2 0 1 0 2-2 2 2 0 0 0-2 2Zm7 0a2 2 0 1 0 2-2 2 2 0 0 0-2 2Zm7 0a2 2 0 1 0 2-2 2 2 0 0 0-2 2Z"/></svg>',
        'api' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 7h10v2H7zm0 4h10v2H7zm0 4h10v2H7z"/></svg>',
        'metrics' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 19h16v2H4zM6 10h3v7H6zm5-4h3v11h-3zm5 2h3v9h-3z"/></svg>',
        'empire' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 10 12 3l9 7v10h-6v-6H9v6H3z"/></svg>',
        'settings' => '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4Zm9 4a7.9 7.9 0 0 0-.2-1.7l2.1-1.6-2-3.4-2.5 1a8 8 0 0 0-3-1.7l-.4-2.7h-4l-.4 2.7a8 8 0 0 0-3 1.7l-2.5-1-2 3.4 2.1 1.6A7.9 7.9 0 0 0 3 12a7.9 7.9 0 0 0 .2 1.7l-2.1 1.6 2 3.4 2.5-1a8 8 0 0 0 3 1.7l.4 2.7h4l.4-2.7a8 8 0 0 0 3-1.7l2.5 1 2-3.4-2.1-1.6A7.9 7.9 0 0 0 21 12Z"/></svg>',
    ];

    $region = \App\Support\OgameUniverse::getRegion();
    $universeNumber = \App\Support\OgameUniverse::getUniverse();
    $flagMap = [
        'ar' => '🇦🇷',
        'br' => '🇧🇷',
        'mx' => '🇲🇽',
        'es' => '🇪🇸',
        'us' => '🇺🇸',
        'de' => '🇩🇪',
        'fr' => '🇫🇷',
        'it' => '🇮🇹',
        'pl' => '🇵🇱',
        'tr' => '🇹🇷',
    ];
    $regionFlag = $flagMap[$region] ?? '';
    $serverName = null;
    $authValidAt = null;
    if (\Illuminate\Support\Facades\Schema::hasTable('app_settings')) {
        $key = 'ogame.server_data.' . $region . '.' . $universeNumber;
        $cached = \App\Models\AppSetting::query()->where('key', $key)->value('value');
        if ($cached) {
            $decoded = json_decode($cached, true);
            if (is_array($decoded) && ! empty($decoded['name'])) {
                $serverName = $decoded['name'];
            }
        }
        $authKey = 'ogame.auth.' . $region . '.' . $universeNumber . '.valid_at';
        $authValidAt = \App\Models\AppSetting::query()->where('key', $authKey)->value('value');
    }
    $serverLabel = $serverName ?: ('Universo ' . $universeNumber);
    $authLogged = $authValidAt !== null;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>BlackOgame</title>
    <link rel="icon" type="image/png" href="/assets/planet.png" />
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header class="topbar">
    <div class="topbar-left">
        <img class="app-logo" src="/assets/planet.png" alt="BlackOgame" />
        <span class="app-name">BlackOgame</span>
        @if ($regionFlag)
            <span class="flag">{{ $regionFlag }}</span>
        @endif
        <span class="topbar-title">{{ $serverLabel }}</span>
    </div>
    <div class="topbar-right">
        <button class="btn {{ $authLogged ? 'success' : '' }} topbar-btn" type="button" data-modal-open="auth-modal">
            {{ $authLogged ? 'Conectado' : 'Login' }}
        </button>
        <span class="topbar-time" data-time>{{ now()->format('H:i') }}</span>
    </div>
</header>

<div class="wrap">
    <nav>
        @foreach ($tabs as $tab)
            <a class="navlink {{ $tab['route'] === $current ? 'active' : '' }}" href="{{ route($tab['route']) }}">
                {!! $icons[$tab['icon']] ?? '' !!}
                {{ $tab['label'] }}
            </a>
        @endforeach
        <a class="navlink {{ $current === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">
            {!! $icons['settings'] !!}
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

@php($loginTest = session('login_test'))
<div class="modal" id="auth-modal" aria-hidden="true" data-login-test="{{ $loginTest ? '1' : '0' }}" data-login-ok="{{ $loginTest && ($loginTest['ok'] ?? false) ? '1' : '0' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel">
        <div class="modal-header">
            <h3>Login OGame</h3>
            <button class="btn ghost" type="button" data-modal-close>Cerrar</button>
        </div>
        <p class="muted">Pegá la cookie del navegador para verificar la sesion.</p>
        <form method="post" action="{{ route('login.test') }}" class="row" style="margin-top:10px">
            @csrf
            <label style="width:100%">
                Header Cookie (completo)
                <textarea name="cookie_header" placeholder="cookie1=...; cookie2=...">{{ old('cookie_header') }}</textarea>
            </label>
            <div class="row" style="justify-content:space-between; width:100%">
                <span class="muted">Si valida, queda logueado.</span>
                <button class="btn secondary" type="submit">Verificar</button>
            </div>
        </form>
        @if ($loginTest)
            <div class="alert {{ ($loginTest['ok'] ?? false) ? 'ok' : 'err' }}" style="margin-top:12px">
                <div>{{ $loginTest['message'] ?? 'Resultado de la validacion.' }}</div>
                <div class="mono" style="margin-top:6px">Estado: {{ $loginTest['status'] ?? '—' }} · Login detectado: {{ $loginTest['login_detected'] ?? '—' }}</div>
                <div class="mono" style="margin-top:6px">{{ $loginTest['snippet'] ?? '' }}</div>
            </div>
        @endif
    </div>
</div>

<footer class="footer">
    Cache local en archivos (para no saturar la API). Versión Laravel 10.
</footer>
<script>
    (function () {
        const el = document.querySelector('[data-time]');
        if (!el) return;
        function tick() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            el.textContent = `${hh}:${mm}`;
        }
        tick();
        setInterval(tick, 60000);
    })();
    (function () {
        const openers = document.querySelectorAll('[data-modal-open]');
        const closers = document.querySelectorAll('[data-modal-close]');
        openers.forEach((btn) => {
            const targetId = btn.getAttribute('data-modal-open');
            const modal = document.getElementById(targetId);
            if (!modal) return;
            btn.addEventListener('click', () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            });
        });
        closers.forEach((btn) => {
            const modal = btn.closest('.modal');
            if (!modal) return;
            btn.addEventListener('click', () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('.modal.is-open').forEach((modal) => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        });
    })();
    (function () {
        const modal = document.getElementById('auth-modal');
        if (!modal) return;
        const hasTest = modal.getAttribute('data-login-test') === '1';
        const ok = modal.getAttribute('data-login-ok') === '1';
        if (hasTest && !ok) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }
    })();
</script>
</body>
</html>
