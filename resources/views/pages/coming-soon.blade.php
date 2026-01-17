@extends('layouts.app')

@php
    $iconMap = [
        'home' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3 3 10v10h6v-6h6v6h6V10z"/></svg>',
        'user' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4Z"/></svg>',
        'planet' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8Zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6Z"/><path fill="currentColor" d="M2 13c4-2 10-3 20-1v2c-10-2-16-1-20 1z"/></svg>',
        'idle' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 4a8 8 0 1 0 8 8 8 8 0 0 0-8-8Zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6Z"/><path fill="currentColor" d="M11 7h2v6h-2zm0 8h2v2h-2z"/></svg>',
        'distance' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 12a2 2 0 1 0 2-2 2 2 0 0 0-2 2Zm7 0a2 2 0 1 0 2-2 2 2 0 0 0-2 2Zm7 0a2 2 0 1 0 2-2 2 2 0 0 0-2 2Z"/></svg>',
        'api' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 7h10v2H7zm0 4h10v2H7zm0 4h10v2H7z"/></svg>',
        'metrics' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 19h16v2H4zM6 10h3v7H6zm5-4h3v11h-3zm5 2h3v9h-3z"/></svg>',
        'settings' => '<svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4Zm9 4a7.9 7.9 0 0 0-.2-1.7l2.1-1.6-2-3.4-2.5 1a8 8 0 0 0-3-1.7l-.4-2.7h-4l-.4 2.7a8 8 0 0 0-3 1.7l-2.5-1-2 3.4 2.1 1.6A7.9 7.9 0 0 0 3 12a7.9 7.9 0 0 0 .2 1.7l-2.1 1.6 2 3.4 2.5-1a8 8 0 0 0 3 1.7l.4 2.7h4l.4-2.7a8 8 0 0 0 3-1.7l2.5 1 2-3.4-2.1-1.6A7.9 7.9 0 0 0 21 12Z"/></svg>',
    ];
@endphp

@section('content')
    <div class="card">
        <h2>
            {!! $iconMap[$icon ?? ''] ?? '' !!}
            {{ $title ?? 'Sección' }}
        </h2>
        <p class="muted">Esta pantalla está en migración desde el código legacy.</p>
    </div>
@endsection
