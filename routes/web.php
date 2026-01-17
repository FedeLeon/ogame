<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::view('/login', 'pages.login')->name('login');
Route::post('/login', function () {
    return back()->with('status', 'Login pendiente de implementar.');
})->name('login.submit');

Route::post('/universe', function (Request $request) {
    $universes = collect(config('ogame.universes', []));
    $selected = (string) $request->input('universe', '');
    $universe = $universes->firstWhere('id', $selected);

    if (! $universe) {
        return back()->with('status', 'Universo inválido.');
    }

    $request->session()->put('universe', $selected);

    return back()->with('status', 'Universo actualizado a ' . $universe['label'] . '.');
})->name('universe.select');

Route::view('/player', 'pages.coming-soon', ['title' => 'Jugador (stats)'])->name('player');
Route::view('/planets', 'pages.coming-soon', ['title' => 'Planetas (buscar)'])->name('planets');
Route::view('/inactives', 'pages.coming-soon', ['title' => 'Inactivos cerca'])->name('inactives');
Route::view('/distance', 'pages.coming-soon', ['title' => 'Distancia'])->name('distance');
Route::view('/api', 'pages.coming-soon', ['title' => 'API raw'])->name('api');
