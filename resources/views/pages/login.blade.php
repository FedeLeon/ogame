@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>
            <svg class="title-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4Z"/></svg>
            Login
        </h2>
        <p class="muted">Por ahora es solo la pantalla. La autenticación se conecta en el próximo paso.</p>

        <form method="post" action="{{ route('login.submit') }}" class="row" style="margin-top:10px">
            @csrf
            <label>
                Email
                <input type="email" name="email" placeholder="tu@email.com" required />
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="••••••••" required />
            </label>
            <button class="btn" type="submit">Entrar</button>
        </form>
    </div>
@endsection
