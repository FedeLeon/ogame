@php
    $selectedUniverse = \App\Support\OgameUniverse::getUniverse();
    $selectedRegion = \App\Support\OgameUniverse::getRegion();
    $selectedBaseUrl = \App\Support\OgameUniverse::baseUrlFor($selectedUniverse, $selectedRegion);
    $isConfigured = \App\Support\OgameUniverse::isConfigured();
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
                                    <option value="ar">Argentina</option>
                                    <option value="br">Brasil</option>
                                    <option value="mx">Mexico</option>
                                    <option value="es">España</option>
                                    <option value="us">Estados Unidos</option>
                                    <option value="de">Alemania</option>
                                    <option value="fr">Francia</option>
                                    <option value="it">Italia</option>
                                    <option value="pl">Polonia</option>
                                    <option value="tr">Turquia</option>
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
                            <p class="muted" data-preview style="margin-top:10px"></p>
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
        <h2>Universo elegido</h2>
        <div class="kpis">
            <div class="kpi">
                <div class="t">Region</div>
                <div class="v mono">{{ strtoupper($selectedRegion) }}</div>
            </div>
            <div class="kpi">
                <div class="t">Universo</div>
                <div class="v mono">{{ $selectedUniverse }}</div>
            </div>
            <div class="kpi">
                <div class="t">Base URL</div>
                <div class="v mono">{{ $selectedBaseUrl }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:12px">
        <h3>Login OGame</h3>
        <p class="muted">Conectate a tu cuenta para acceder a datos adicionales.</p>
        <a class="btn" href="{{ route('login') }}">Ir al login</a>
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
                const preview = form.querySelector('[data-preview]');
                const baseTemplate = @json(config('ogame.base_url_template'));

                function showStep(step) {
                    steps.forEach((el) => {
                        el.hidden = el.getAttribute('data-step') !== String(step);
                    });
                }

                function updatePreview() {
                    const region = regionSelect.value;
                    const universe = universeInput.value;
                    if (!region || !universe) {
                        preview.textContent = '';
                        return;
                    }
                    const url = baseTemplate.replace('{universe}', universe).replace('{region}', region);
                    preview.textContent = 'Base URL: ' + url;
                }

                nextBtn.addEventListener('click', () => {
                    if (!regionSelect.value) return;
                    showStep(2);
                    updatePreview();
                });

                backBtn.addEventListener('click', () => showStep(1));
                universeInput.addEventListener('input', updatePreview);
                regionSelect.addEventListener('change', updatePreview);
            })();
        </script>
    @endif
@endsection
