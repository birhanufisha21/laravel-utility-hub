<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are consumed by the Alpine.js frontend components.
| All responses are JSON.
|
*/

// Currency conversion endpoint
Route::post('/currency/convert', [CurrencyController::class, 'convert'])->name('api.currency.convert');

// Weather search endpoint
Route::get('/weather/search', [WeatherController::class, 'search'])->name('api.weather.search');
