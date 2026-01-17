@php
    $selectedUniverse = \App\Support\OgameUniverse::getUniverse();
    $selectedRegion = \App\Support\OgameUniverse::getRegion();
    $selectedBaseUrl = \App\Support\OgameUniverse::baseUrlFor($selectedUniverse, $selectedRegion);
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
@endphp
@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Configuracion</h2>
        <p class="muted">Actualiza tu region y universo cuando quieras.</p>

        <form method="post" action="{{ route('settings.save') }}" class="row" style="margin-top:10px">
            @csrf
            <label>
                Pais
                <select name="region" required>
                    <option value="ar" @selected($selectedRegion === 'ar')>{{ $flagMap['ar'] }} Argentina</option>
                    <option value="br" @selected($selectedRegion === 'br')>{{ $flagMap['br'] }} Brasil</option>
                    <option value="mx" @selected($selectedRegion === 'mx')>{{ $flagMap['mx'] }} Mexico</option>
                    <option value="es" @selected($selectedRegion === 'es')>{{ $flagMap['es'] }} España</option>
                    <option value="us" @selected($selectedRegion === 'us')>{{ $flagMap['us'] }} Estados Unidos</option>
                    <option value="de" @selected($selectedRegion === 'de')>{{ $flagMap['de'] }} Alemania</option>
                    <option value="fr" @selected($selectedRegion === 'fr')>{{ $flagMap['fr'] }} Francia</option>
                    <option value="it" @selected($selectedRegion === 'it')>{{ $flagMap['it'] }} Italia</option>
                    <option value="pl" @selected($selectedRegion === 'pl')>{{ $flagMap['pl'] }} Polonia</option>
                    <option value="tr" @selected($selectedRegion === 'tr')>{{ $flagMap['tr'] }} Turquia</option>
                </select>
            </label>
            <label>
                Universo
                <input type="number" name="universe" value="{{ $selectedUniverse }}" required />
            </label>
            <button class="btn" type="submit">Guardar</button>
        </form>

        <div class="muted" style="margin-top:10px">
            Base URL actual: <span class="mono">{{ $selectedBaseUrl }}</span>
        </div>
    </div>
@endsection
