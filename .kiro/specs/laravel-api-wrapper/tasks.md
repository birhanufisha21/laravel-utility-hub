# Implementation Plan: Laravel API Wrapper

## Overview

Implement a portfolio-quality Laravel application with a Currency Converter and Weather Dashboard. The implementation follows a service-layer architecture with thin controllers, caching, Form Request validation, structured error handling, and Alpine.js frontend components. No database is required — all data is fetched from external APIs and cached transiently.

## Tasks

- [ ] 1. Project scaffolding and configuration
  - Create `config/services.php` entries for `exchange_rate` (key, base_url) and `open_meteo` (geocoding_url, weather_url)
  - Create `.env.example` with all required environment variables and inline comments: `EXCHANGE_RATE_API_KEY`, `EXCHANGE_RATE_API_BASE_URL`, `OPEN_METEO_GEOCODING_URL`, `OPEN_METEO_WEATHER_URL`, `CACHE_DRIVER`
  - Register `CurrencyService` and `WeatherService` bindings in `AppServiceProvider` (or rely on automatic resolution)
  - Add `eris/eris` to `composer.json` as a dev dependency and run `composer require --dev eris/eris`
  - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [ ] 2. Custom exceptions
  - [ ] 2.1 Create `App\Exceptions\ApiException`
    - Extend `\RuntimeException`; no additional fields required
    - _Requirements: 1.4, 2.4_
  - [ ] 2.2 Create `App\Exceptions\RateLimitException`
    - Extend `\RuntimeException`; no additional fields required
    - _Requirements: 6.1, 6.2_
  - [ ] 2.3 Create `App\Exceptions\CityNotFoundException`
    - Extend `\RuntimeException`; no additional fields required
    - _Requirements: 2.5_

- [ ] 3. Form Request classes
  - [ ] 3.1 Create `App\Http\Requests\CurrencyConvertRequest`
    - Rules: `amount` → `required|numeric|gt:0`; `from` → `required|string|size:3|regex:/^[A-Z]{3}$/`; `to` → `required|string|size:3|regex:/^[A-Z]{3}$/`
    - Override `failedValidation()` to throw a `ValidationException` that returns a JSON 422 response with `success`, `message`, and `errors` keys
    - _Requirements: 3.1, 3.3, 3.4_
  - [ ] 3.2 Create `App\Http\Requests\WeatherSearchRequest`
    - Rules: `city` → `required|string|max:100`
    - Override `failedValidation()` to return a JSON 422 response with `success`, `message`, and `errors` keys
    - _Requirements: 3.2, 3.3, 3.4_

- [ ] 4. CurrencyService implementation
  - [ ] 4.1 Create `App\Services\CurrencyService` with `convert()`, `getRates()`, and `fetchFromApi()` methods
    - `fetchFromApi(string $base): array` — calls `Http::timeout(10)->get(...)` using `config('services.exchange_rate.base_url')` and `config('services.exchange_rate.key')`; checks for 429 → throw `RateLimitException`; checks `$response->failed()` → throw `ApiException`; returns rates array
    - `getRates(string $base): array` — checks `Cache::get("currency.rates.{$base}")`; on miss calls `fetchFromApi()` and stores result with `Cache::put(..., now()->addMinutes(60))`
    - `convert(float $amount, string $from, string $to): array` — calls `getRates($from)`, computes `$rate = $rates[$to]`, returns `['converted_amount' => round($amount * $rate, 2), 'rate' => $rate, 'from' => $from, 'to' => $to]`
    - Support minimum 30 currency codes in the rates map (validated by the external API response)
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 6.1, 7.1, 7.2_
  - [ ]* 4.2 Write property test for CurrencyService — Property 1: conversion math
    - **Property 1: Currency conversion math is correct**
    - Use Eris `float()` generator (positive values) and `elements()` from a mocked rates map to assert `converted_amount === amount × rate` within floating-point tolerance
    - Tag: `// Feature: laravel-api-wrapper, Property 1: Currency conversion math is correct`
    - **Validates: Requirements 1.2**
    - _File: `tests/Unit/Properties/CurrencyPropertyTest.php`_
  - [ ]* 4.3 Write property test for CurrencyService — Property 2: cache prevents duplicate HTTP calls
    - **Property 2: Cached exchange rates are served without external API calls**
    - Use `Http::fake()` and Eris `elements()` from currency list; call `getRates()` twice; assert `Http::assertSentCount(1)`
    - Tag: `// Feature: laravel-api-wrapper, Property 2: Cached exchange rates are served without external API calls`
    - **Validates: Requirements 1.6, 1.7**
    - _File: `tests/Unit/Properties/CurrencyPropertyTest.php`_
  - [ ]* 4.4 Write unit tests for CurrencyService
    - Test: correct converted amount for known rates
    - Test: `ApiException` thrown when Http Client returns 5xx
    - Test: `RateLimitException` thrown when Http Client returns 429
    - Test: cache populated after successful fetch (`Cache::has()`)
    - Test: Http called only once on second `getRates()` call
    - _File: `tests/Unit/Services/CurrencyServiceTest.php`_
    - _Requirements: 1.2, 1.3, 1.4, 1.6, 1.7, 6.1_

- [ ] 5. WeatherService implementation
  - [ ] 5.1 Create `App\Services\WeatherService` with `getWeather()`, `geocode()`, and `fetchWeather()` methods
    - `geocode(string $city): array` — calls Open-Meteo geocoding URL via `Http::timeout(10)->get(...)`; if results array is empty throws `CityNotFoundException`; returns `['lat', 'lon', 'display_name', 'country']`
    - `fetchWeather(float $lat, float $lon): array` — calls Open-Meteo weather URL requesting `current_weather`, `relativehumidity_2m`, `windspeed_10m`; checks 429 → `RateLimitException`; checks `$response->failed()` → `ApiException`; maps WMO code to condition string using `WMO_CODES` constant; computes `temperature_f = ($c * 9 / 5) + 32`
    - `getWeather(string $city): array` — normalises city with `strtolower(trim($city))`; checks `Cache::get("weather.{$normalised}")`; on miss calls `geocode()` then `fetchWeather()` and stores with `Cache::put(..., now()->addMinutes(10))`; returns full response array
    - Include complete `WMO_CODES` lookup table (codes 0–3, 45, 48, 51–57, 61–67, 71–77, 80–82, 85–86, 95, 96, 99)
    - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 5.5, 6.1, 7.1, 7.2_
  - [ ]* 5.2 Write property test for WeatherService — Property 3: response contains all required fields
    - **Property 3: Weather response contains all required fields for any valid input**
    - Use Eris `elements()` from WMO codes and `float()` for temp/humidity/wind; assert response contains `temperature_c`, `temperature_f`, `condition` (non-empty), `humidity`, `wind_speed_kmh`
    - Tag: `// Feature: laravel-api-wrapper, Property 3: Weather response contains all required fields for any valid input`
    - **Validates: Requirements 2.2, 5.5**
    - _File: `tests/Unit/Properties/WeatherPropertyTest.php`_
  - [ ]* 5.3 Write property test for WeatherService — Property 4: cache prevents duplicate HTTP calls
    - **Property 4: Cached weather data is served without external API calls**
    - Use Eris `string()` generator (non-empty, ≤100 chars, random casing/whitespace); pre-populate cache for normalised key; call `getWeather()` and assert `Http::assertNothingSent()`
    - Tag: `// Feature: laravel-api-wrapper, Property 4: Cached weather data is served without external API calls`
    - **Validates: Requirements 2.6, 2.7**
    - _File: `tests/Unit/Properties/WeatherPropertyTest.php`_
  - [ ]* 5.4 Write property test for WeatherService — Property 7: Celsius-to-Fahrenheit conversion
    - **Property 7: Celsius-to-Fahrenheit conversion is mathematically consistent**
    - Use Eris `float()` bounded to -100..100; assert `temperature_f === (c × 9/5) + 32` within 0.01 tolerance
    - Tag: `// Feature: laravel-api-wrapper, Property 7: Celsius-to-Fahrenheit conversion is mathematically consistent`
    - **Validates: Requirements 5.5**
    - _File: `tests/Unit/Properties/WeatherPropertyTest.php`_
  - [ ]* 5.5 Write unit tests for WeatherService
    - Test: `CityNotFoundException` thrown when geocoding returns empty results
    - Test: WMO code 0 maps to "Clear sky", code 95 maps to "Thunderstorm"
    - Test: cache populated after successful fetch
    - Test: Http called only once on second `getWeather()` call for same city
    - _File: `tests/Unit/Services/WeatherServiceTest.php`_
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7_

- [ ] 6. Checkpoint — Ensure all service-layer tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Controllers
  - [ ] 7.1 Create `App\Http\Controllers\CurrencyController`
    - `index()` — returns `view('currency.index')`
    - `convert(CurrencyConvertRequest $request)` — injects `CurrencyService`; calls `$service->convert(...)`; wraps in try/catch for `RateLimitException` (429 + log warning), `ApiException` (502), `\Throwable` (500 + log error); returns `JsonResponse` with `success`, `from`, `to`, `amount`, `converted_amount`, `rate`
    - _Requirements: 1.2, 1.4, 6.2, 6.3, 7.1_
  - [ ] 7.2 Create `App\Http\Controllers\WeatherController`
    - `index()` — returns `view('weather.index')`
    - `search(WeatherSearchRequest $request)` — injects `WeatherService`; calls `$service->getWeather(...)`; wraps in try/catch for `RateLimitException` (429 + log warning), `CityNotFoundException` (404), `ApiException` (502), `\Throwable` (500 + log error); returns `JsonResponse` with all required weather fields
    - _Requirements: 2.2, 2.4, 2.5, 6.2, 6.3, 7.1_
  - [ ] 7.3 Register routes in `routes/web.php` and `routes/api.php`
    - `web.php`: `GET /currency` → `CurrencyController@index`; `GET /weather` → `WeatherController@index`
    - `api.php`: `POST /api/currency/convert` → `CurrencyController@convert`; `GET /api/weather/search` → `WeatherController@search`
    - _Requirements: 1.1, 2.1_

- [ ] 8. Controller feature tests
  - [ ]* 8.1 Write property test — Property 5: invalid currency inputs rejected before HTTP call
    - **Property 5: Invalid currency conversion inputs are rejected before any external call**
    - Use Eris `float()` (non-positive) and `string()` (invalid codes); assert HTTP 422 and `Http::assertNothingSent()`
    - Tag: `// Feature: laravel-api-wrapper, Property 5: Invalid currency conversion inputs are rejected before any external call`
    - **Validates: Requirements 3.1, 3.3, 3.4**
    - _File: `tests/Unit/Properties/CurrencyPropertyTest.php`_
  - [ ]* 8.2 Write property test — Property 6: invalid weather inputs rejected before HTTP call
    - **Property 6: Invalid weather search inputs are rejected before any external call**
    - Use Eris `string()` (length > 100) and constant `""`; assert HTTP 422 and `Http::assertNothingSent()`
    - Tag: `// Feature: laravel-api-wrapper, Property 6: Invalid weather search inputs are rejected before any external call`
    - **Validates: Requirements 3.2, 3.3, 3.4**
    - _File: `tests/Unit/Properties/WeatherPropertyTest.php`_
  - [ ]* 8.3 Write feature tests for CurrencyController
    - `POST /api/currency/convert` with valid data → 200 with `success`, `converted_amount`, `rate`, `from`, `to`
    - `POST /api/currency/convert` with invalid amount → 422 with `errors` key
    - `POST /api/currency/convert` when service throws `ApiException` → 502
    - `POST /api/currency/convert` when service throws `RateLimitException` → 429
    - `GET /currency` → 200 (Blade view renders)
    - _File: `tests/Feature/CurrencyControllerTest.php`_
    - _Requirements: 1.1, 1.2, 1.4, 3.1, 3.3, 6.2_
  - [ ]* 8.4 Write feature tests for WeatherController
    - `GET /api/weather/search?city=London` → 200 with all required weather fields
    - `GET /api/weather/search` with empty city → 422
    - `GET /api/weather/search?city=Xyzabc` when geocoding returns empty → 404
    - `GET /weather` → 200 (Blade view renders)
    - _File: `tests/Feature/WeatherControllerTest.php`_
    - _Requirements: 2.1, 2.2, 2.4, 2.5, 3.2, 3.3_

- [ ] 9. Checkpoint — Ensure all controller and feature tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 10. Blade views and Alpine.js — Currency Converter
  - [ ] 10.1 Create Blade layout `resources/views/layouts/app.blade.php`
    - Include Alpine.js CDN `<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>` in `<head>`
    - Provide `@yield('content')` slot
    - _Requirements: 4.5_
  - [ ] 10.2 Create `resources/views/currency/index.blade.php`
    - Extend layout; define Alpine component via `x-data` with fields: `amount`, `from`, `to`, `loading`, `result`, `error`
    - Form with amount input, source currency `<select>` (minimum 30 options), target currency `<select>`, submit button
    - Submit button disabled and loading indicator shown while `loading === true`
    - On submit: `fetch('POST', '/api/currency/convert', {amount, from, to})`; on success display `converted_amount` and `rate`; on error display `error.message`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 11. Blade views and Alpine.js — Weather Dashboard
  - [ ] 11.1 Create `resources/views/weather/index.blade.php`
    - Extend layout; define Alpine component via `x-data` with fields: `city`, `loading`, `result`, `error`
    - Form with city name text input and submit button
    - Submit button disabled and loading indicator shown while `loading === true`
    - On submit: `fetch('GET', '/api/weather/search?city=...')`; on success display `temperature_c`, `temperature_f`, `condition`, `humidity`, `wind_speed_kmh`; on error display `error.message`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 12. README and final wiring
  - [ ] 12.1 Create `README.md`
    - Setup instructions (clone, `composer install`, copy `.env.example` to `.env`, set `EXCHANGE_RATE_API_KEY`, run `php artisan serve`)
    - List all required environment variables with descriptions
    - Brief description of Currency Converter and Weather Dashboard features
    - _Requirements: 7.5_
  - [ ] 12.2 Verify all routes, services, and views are wired together end-to-end
    - Confirm `AppServiceProvider` bindings resolve correctly
    - Confirm `config/services.php` keys match all `config()` calls in service classes
    - Confirm Alpine.js `fetch` URLs match registered API routes
    - _Requirements: 7.1, 7.2, 7.3_

- [ ] 13. Final checkpoint — Ensure all tests pass
  - Run `php artisan test` and confirm all unit, feature, and property tests pass. Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Each task references specific requirements for traceability
- Property tests use the [Eris](https://github.com/giorgiosironi/eris) library and run a minimum of 100 iterations per property
- Unit tests use `Http::fake()` to avoid real external API calls
- No database migrations are needed — all data is transient
- Alpine.js is loaded from CDN; no build step (Vite/Mix) is required for the frontend
