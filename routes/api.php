<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::controller(WeatherController::class)->group(function (): void {
    Route::get(
        uri: '/weather',
        action: 'search',
    )->name('weather.show');
});
