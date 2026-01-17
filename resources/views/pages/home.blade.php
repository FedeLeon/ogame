@php
    $selectedUniverse = \App\Support\OgameUniverse::getUniverse();
    $selectedRegion = \App\Support\OgameUniverse::getRegion();
    $isConfigured = \App\Support\OgameUniverse::isConfigured();
    $serverData = $serverData ?? null;
    $serverError = $serverError ?? null;
    $selfPlayer = $selfPlayer ?? null;
    $selfPlanets = $selfPlanets ?? [];
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
    $regionFlag = $flagMap[$selectedRegion] ?? '';
    $toPercent = function ($value) {
        $num = is_numeric($value) ? (float) $value : null;
        return $num === null ? '—' : rtrim(rtrim(number_format($num * 100, 2, '.', ''), '0'), '.') . '%';
    };
    $toBool = function ($value, string $yes = 'Si', string $no = 'No') {
        if ($value === '') {
            return '—';
        }
        return in_array((string) $value, ['1', 'true', 'yes'], true) ? $yes : $no;
    };
    $toBoolDefaultNo = function ($value) use ($toBool) {
        return $toBool($value !== '' ? $value : '0');
    };
    $formatSpeed = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }
        return 'x' . $value;
    };
    $fleetSpeeds = [];
    if (is_array($serverData)) {
        if (! empty($serverData['speedFleetPeaceful'])) {
            $fleetSpeeds[] = ['label' => 'Flota paz', 'value' => $serverData['speedFleetPeaceful']];
        }
        if (! empty($serverData['speedFleetWar'])) {
            $fleetSpeeds[] = ['label' => 'Flota guerra', 'value' => $serverData['speedFleetWar']];
        }
        if (! empty($serverData['speedFleetHolding'])) {
            $fleetSpeeds[] = ['label' => 'Flota defensiva', 'value' => $serverData['speedFleetHolding']];
        }
        if (count($fleetSpeeds) === 0 && ! empty($serverData['speedFleet'])) {
            $fleetSpeeds[] = ['label' => 'Vel. flota', 'value' => $serverData['speedFleet']];
        }
    }
    $developmentSpeed = $serverData['speed'] ?? ($serverData['speedEconomy'] ?? null);
    $explorationSpeed = $serverData['speedExpedition'] ?? null;
    $deuteriumSave = $serverData['globalDeuteriumSaveFactor'] ?? null;
    $deuteriumConsumption = $serverData['deuteriumConsumption'] ?? null;
    $darkMatterBonus = $serverData['darkMatterNewAcount'] ?? ($serverData['darkMatterNewPlayer'] ?? null);
@endphp
@extends('layouts.app')

@section('content')
    @if (! $isConfigured)
        <div class="onboarding" data-onboarding>
            <div class="onboarding-frame">
                <div class="card">
                    <h2>Configura tu universo</h2>
                    <p class="muted">Un paso rapido para armar la URL de tu servidor.</p>

                    <form method="post" action="{{ route('settings.save') }}" data-onboarding-form>
                        @csrf
                        <div class="onboarding-step" data-step="1">
                            <label>
                                Pais
                                <select name="region" required>
                                    <option value="" selected disabled>Elegi tu pais</option>
                                    <option value="ar">🇦🇷 Argentina</option>
                                    <option value="br">🇧🇷 Brasil</option>
                                    <option value="mx">🇲🇽 Mexico</option>
                                    <option value="es">🇪🇸 España</option>
                                    <option value="us">🇺🇸 Estados Unidos</option>
                                    <option value="de">🇩🇪 Alemania</option>
                                    <option value="fr">🇫🇷 Francia</option>
                                    <option value="it">🇮🇹 Italia</option>
                                    <option value="pl">🇵🇱 Polonia</option>
                                    <option value="tr">🇹🇷 Turquia</option>
                                </select>
                            </label>
                            <div class="row" style="margin-top:10px">
                                <button class="btn" type="button" data-step-next>Siguiente</button>
                            </div>
                        </div>

                        <div class="onboarding-step" data-step="2" hidden>
                            <label>
                                Universo
                                <input type="number" name="universe" placeholder="164" required />
                            </label>
                            <div class="row" style="margin-top:10px">
                                <button class="btn secondary" type="button" data-step-back>Atras</button>
                                <button class="btn" type="submit">Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3 3 10v10h6v-6h6v6h6V10z"/></svg>
            Universo elegido
        </h2>
        <div class="kpis">
            <div class="kpi">
                <div class="t">Region</div>
                <div class="v mono">
                    @if ($regionFlag)
                        <span class="flag">{{ $regionFlag }}</span>
                    @endif
                    {{ strtoupper($selectedRegion) }}
                </div>
            </div>
            <div class="kpi">
                <div class="t">Universo</div>
                <div class="v mono">{{ $selectedUniverse }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:12px">
        <h3>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4Z"/></svg>
            Mi perfil
        </h3>
        @if ($selfPlayer)
            <div class="kpis">
                <div class="kpi">
                    <div class="t">Jugador</div>
                    <div class="v mono">{{ $selfPlayer->name }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Status</div>
                    <div class="v mono">{{ $selfPlayer->status ?: '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Planetas</div>
                    <div class="v mono">{{ $selfPlayer->planets_count }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Coordenadas</div>
                    <div class="v">
                        @if (count($selfPlanets))
                            <ul class="coord-list mono">
                                @foreach ($selfPlanets as $coord)
                                    <li>{{ $coord }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="mono">—</span>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <p class="muted">Selecciona tu usuario en Configuracion para ver tu perfil.</p>
        @endif
    </div>

    <div class="card" style="margin-top:12px">
        <div class="card-header">
            <h3>
                <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 7h10v2H7zm0 4h10v2H7zm0 4h10v2H7z"/></svg>
                Server Data
            </h3>
            <a class="btn success" href="{{ route('home', ['refresh' => 1]) }}">
                <svg class="icon-refresh" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M3 12a9 9 0 0 1 15.55-6.36L21 3v6h-6l2.17-2.17A7 7 0 1 0 19 12h2a9 9 0 0 1-18 0Z"/>
                </svg>
                Refrescar
            </a>
        </div>
        @if (! $isConfigured)
            <p class="muted">Configura tu universo para ver los datos del servidor.</p>
        @elseif ($serverError)
            <div class="alert err">Error: {{ $serverError }}</div>
        @elseif (is_array($serverData))
            <div class="kpis">
                <div class="kpi">
                    <div class="t">Dominio</div>
                    <div class="v mono">{{ $serverData['domain'] ?? '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Idioma</div>
                    <div class="v mono">{{ $serverData['language'] ?? '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Zona horaria</div>
                    <div class="v mono">{{ $serverData['timezone'] ?? '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Version</div>
                    <div class="v mono">{{ $serverData['version'] ?? '—' }}</div>
                </div>
                @foreach ($fleetSpeeds as $speed)
                    <div class="kpi">
                        <div class="t">{{ $speed['label'] }}</div>
                        <div class="v mono">{{ $formatSpeed($speed['value']) }}</div>
                    </div>
                @endforeach
                <div class="kpi">
                    <div class="t">Desarrollo</div>
                    <div class="v mono">{{ $formatSpeed($developmentSpeed) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Investigacion</div>
                    <div class="v mono">{{ $formatSpeed($serverData['speedResearch'] ?? null) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Exploracion</div>
                    <div class="v mono">{{ $formatSpeed($explorationSpeed) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Galaxias</div>
                    <div class="v mono">{{ $serverData['galaxies'] ?? '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Sistemas</div>
                    <div class="v mono">{{ $serverData['systems'] ?? '—' }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Escombros flota</div>
                    <div class="v mono">{{ $toPercent($serverData['debrisFactor'] ?? null) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Escombros defensa</div>
                    <div class="v mono">{{ $toPercent($serverData['debrisFactorDef'] ?? null) }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Consumo de deuterio</div>
                    <div class="v mono">
                        @if ($deuteriumConsumption !== null && $deuteriumConsumption !== '')
                            {{ $toPercent($deuteriumConsumption) }}
                        @elseif ($deuteriumSave !== null && $deuteriumSave !== '')
                            -{{ $toPercent($deuteriumSave) }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
            @php
                $rules = [];
                $addRule = function (string $label, ?string $value) use (&$rules) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $rules[] = ['label' => $label, 'value' => $value];
                };

                $addRule('Servidor', ($serverData['name'] ?? '') ? ($serverData['name'] . ' (s' . ($serverData['number'] ?? '') . '-' . ($serverData['language'] ?? '') . ')') : null);
                $addRule('Zona horaria', ($serverData['timezone'] ?? '') ? ($serverData['timezone'] . ' ' . ($serverData['timezoneOffset'] ?? '')) : null);
                $addRule('Sistema de alianzas activo', $toBoolDefaultNo($serverData['acs'] ?? ''));
                $addRule('Rapid Fire activo', $toBoolDefaultNo($serverData['rapidFire'] ?? ''));
                $addRule('Defensa a escombros', $toBoolDefaultNo($serverData['defToTF'] ?? ''));
                $addRule('Escombros flota', $toPercent($serverData['debrisFactor'] ?? null));
                $addRule('Escombros defensa', $toPercent($serverData['debrisFactorDef'] ?? null));
                $addRule('Deuterio en escombros', $toBoolDefaultNo($serverData['deuteriumInDebris'] ?? ''));
                $addRule('Consumo de deuterio reducido', $deuteriumSave !== null && $deuteriumSave !== '' ? $toPercent($deuteriumSave) : null);
                $addRule('Ignora sistemas vacios', $toBoolDefaultNo($serverData['fleetIgnoreEmptySystems'] ?? ''));
                $addRule('Ignora sistemas inactivos', $toBoolDefaultNo($serverData['fleetIgnoreInactiveSystems'] ?? ''));
                $addRule('Numero de galaxias', $serverData['galaxies'] ?? null);
                $addRule('Sistemas por galaxia', $serverData['systems'] ?? null);
                $addRule('Bonus campos planeta', $serverData['bonusFields'] ?? null);
                $addRule('Velocidad desarrollo', $developmentSpeed ? 'x' . $developmentSpeed : null);
                $addRule('Velocidad investigacion', ($serverData['speedResearch'] ?? '') ? 'x' . $serverData['speedResearch'] : null);
                $addRule('Velocidad exploracion', $explorationSpeed ? 'x' . $explorationSpeed : null);
                $addRule('Proteccion novatos (score)', $serverData['newbieProtectionLimit'] ?? null);
                $addRule('Proteccion novatos alta (score)', $serverData['newbieProtectionHigh'] ?? null);
                $addRule('Top score', $serverData['topScore'] ?? null);
                $addRule('Reparacion defensas (TF)', $serverData['repairFactor'] ?? null);
                $addRule('Sistema de bash', $toBoolDefaultNo($serverData['bashingSystemEnabled'] ?? ''));
                $addRule('Limite de bash', $serverData['bashlimit'] ?? null);
                $addRule('Marketplace activo', $toBoolDefaultNo($serverData['marketplaceEnabled'] ?? ''));
            @endphp
            @if (count($rules) > 0)
                <div class="card" style="margin-top:12px">
                    <h3>Reglas del servidor</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Regla</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rules as $rule)
                                <tr>
                                    <td>{{ $rule['label'] }}</td>
                                    <td class="mono">{{ $rule['value'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <p class="muted">Sin datos disponibles.</p>
        @endif
    </div>

    @if (! $isConfigured)
        <script>
            (function () {
                const form = document.querySelector('[data-onboarding-form]');
                if (!form) return;

                const steps = form.querySelectorAll('[data-step]');
                const nextBtn = form.querySelector('[data-step-next]');
                const backBtn = form.querySelector('[data-step-back]');
                const regionSelect = form.querySelector('select[name="region"]');
                const universeInput = form.querySelector('input[name="universe"]');
                function showStep(step) {
                    steps.forEach((el) => {
                        el.hidden = el.getAttribute('data-step') !== String(step);
                    });
                }

                nextBtn.addEventListener('click', () => {
                    if (!regionSelect.value) return;
                    showStep(2);
                });

                backBtn.addEventListener('click', () => showStep(1));
            })();
        </script>
    @endif
@endsection
