<?php

use App\Http\Controllers\Web\WeatherController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get(
    uri: 'weather',
    action: [WeatherController::class, 'index'],
)->name('weather.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
