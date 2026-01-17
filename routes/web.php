<?php

use App\Support\OgameUniverse;
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

Route::get('/settings', function () {
    return view('pages.settings');
})->name('settings');

Route::post('/settings', function (Request $request) {
    $selected = (string) $request->input('universe', '');
    $selected = preg_replace('/\D/', '', $selected ?? '');
    $region = strtolower(trim((string) $request->input('region', '')));

    if ($selected === '' || $region === '') {
        return back()->with('status', 'Universo o region invalida.');
    }

    $allowedRegions = ['ar', 'br', 'mx', 'es', 'us', 'de', 'fr', 'it', 'pl', 'tr'];
    if (! in_array($region, $allowedRegions, true)) {
        return back()->with('status', 'Region invalida.');
    }

    $request->session()->put('universe', $selected);
    $request->session()->put('region', $region);
    OgameUniverse::storeUniverse($selected);
    OgameUniverse::storeRegion($region);

    return redirect()->route('home')->with('status', 'Configuracion actualizada.');
})->name('settings.save');

Route::view('/player', 'pages.coming-soon', ['title' => 'Jugador (stats)'])->name('player');
Route::view('/planets', 'pages.coming-soon', ['title' => 'Planetas (buscar)'])->name('planets');
Route::view('/inactives', 'pages.coming-soon', ['title' => 'Inactivos cerca'])->name('inactives');
Route::view('/distance', 'pages.coming-soon', ['title' => 'Distancia'])->name('distance');
Route::view('/api', 'pages.coming-soon', ['title' => 'API raw'])->name('api');
