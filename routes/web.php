<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Currency Converter page
Route::get('/currency', [CurrencyController::class, 'index'])->name('currency.index');

// Weather Dashboard page
Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');
