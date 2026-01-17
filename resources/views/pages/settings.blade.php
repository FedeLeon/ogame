@php
    $selectedUniverse = \App\Support\OgameUniverse::getUniverse();
    $selectedRegion = \App\Support\OgameUniverse::getRegion();
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
    $selfId = $selfId ?? '';
    $players = $players ?? collect();
@endphp
@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4Zm9 4a7.9 7.9 0 0 0-.2-1.7l2.1-1.6-2-3.4-2.5 1a8 8 0 0 0-3-1.7l-.4-2.7h-4l-.4 2.7a8 8 0 0 0-3 1.7l-2.5-1-2 3.4 2.1 1.6A7.9 7.9 0 0 0 3 12a7.9 7.9 0 0 0 .2 1.7l-2.1 1.6 2 3.4 2.5-1a8 8 0 0 0 3 1.7l.4 2.7h4l.4-2.7a8 8 0 0 0 3-1.7l2.5 1 2-3.4-2.1-1.6A7.9 7.9 0 0 0 21 12Z"/></svg>
            Configuracion
        </h2>
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
            <label>
                Mi usuario
                <input type="text" name="self_player_id" list="player-list" value="{{ $selfId }}" placeholder="Escribi tu usuario" />
                <datalist id="player-list">
                    @foreach ($players as $player)
                        <option value="{{ $player->ogame_id }}">{{ $player->name }}</option>
                    @endforeach
                </datalist>
            </label>
            <button class="btn" type="submit">Guardar</button>
        </form>

    </div>
@endsection
