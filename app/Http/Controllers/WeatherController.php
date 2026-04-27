<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\CityNotFoundException;
use App\Exceptions\RateLimitException;
use App\Http\Requests\WeatherSearchRequest;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    public function __construct(private readonly WeatherService $weatherService)
    {
    }

    /**
     * Render the Weather Dashboard Blade view.
     */
    public function index()
    {
        return view('weather.index');
    }

    /**
     * Handle a weather search request.
     * GET /api/weather/search?city={city}
     */
    public function search(WeatherSearchRequest $request): JsonResponse
    {
        try {
            $weather = $this->weatherService->getWeather($request->validated('city'));

            return response()->json([
                'success'        => true,
                'city'           => $weather['city'],
                'country'        => $weather['country'],
                'temperature_c'  => $weather['temperature_c'],
                'temperature_f'  => $weather['temperature_f'],
                'condition'      => $weather['condition'],
                'humidity'       => $weather['humidity'],
                'wind_speed_kmh' => $weather['wind_speed_kmh'],
            ]);
        } catch (RateLimitException $e) {
            Log::warning('Rate limit hit', [
                'service' => 'weather',
                'at'      => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests to the weather service. Please try again later.',
                'code'    => 'RATE_LIMITED',
            ], 429);
        } catch (CityNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => 'CITY_NOT_FOUND',
            ], 404);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The weather service is currently unavailable.',
                'code'    => 'API_ERROR',
            ], 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in WeatherController@search', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }
}
