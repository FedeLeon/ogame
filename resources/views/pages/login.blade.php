@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Login</h2>
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
