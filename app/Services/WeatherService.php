<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\CityNotFoundException;
use App\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    /**
     * WMO Weather Interpretation Codes mapped to human-readable condition strings.
     * Reference: https://open-meteo.com/en/docs#weathervariables
     */
    private const WMO_CODES = [
        0  => 'Clear sky',
        1  => 'Mainly clear',
        2  => 'Partly cloudy',
        3  => 'Overcast',
        45 => 'Foggy',
        48 => 'Icy fog',
        51 => 'Light drizzle',
        53 => 'Moderate drizzle',
        55 => 'Dense drizzle',
        56 => 'Light freezing drizzle',
        57 => 'Heavy freezing drizzle',
        61 => 'Slight rain',
        63 => 'Moderate rain',
        65 => 'Heavy rain',
        66 => 'Light freezing rain',
        67 => 'Heavy freezing rain',
        71 => 'Slight snowfall',
        73 => 'Moderate snowfall',
        75 => 'Heavy snowfall',
        77 => 'Snow grains',
        80 => 'Slight rain showers',
        81 => 'Moderate rain showers',
        82 => 'Violent rain showers',
        85 => 'Slight snow showers',
        86 => 'Heavy snow showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm with slight hail',
        99 => 'Thunderstorm with heavy hail',
    ];

    /**
     * Resolve a city name to geographic coordinates via Open-Meteo geocoding.
     *
     * @param  string  $city  City name to geocode
     * @return array{lat: float, lon: float, display_name: string, country: string}
     *
     * @throws CityNotFoundException  If no results are returned for the city name
     * @throws ApiException           If the geocoding API returns an error
     */
    public function geocode(string $city): array
    {
        $url = config('services.open_meteo.geocoding_url');

        $response = Http::timeout(10)->get($url, [
            'name'    => $city,
            'count'   => 1,
            'language' => 'en',
            'format'  => 'json',
        ]);

        if ($response->failed()) {
            throw new ApiException(
                "Open-Meteo geocoding API returned HTTP {$response->status()} for city '{$city}'."
            );
        }

        $data    = $response->json();
        $results = $data['results'] ?? [];

        if (empty($results)) {
            throw new CityNotFoundException("City '{$city}' could not be found.");
        }

        $result = $results[0];

        return [
            'lat'          => (float) $result['latitude'],
            'lon'          => (float) $result['longitude'],
            'display_name' => $result['name'] ?? $city,
            'country'      => $result['country_code'] ?? '',
        ];
    }

    /**
     * Fetch current weather data for given coordinates from Open-Meteo.
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @return array{temperature_c: float, temperature_f: float, condition: string, humidity: int, wind_speed_kmh: float}
     *
     * @throws RateLimitException
     * @throws ApiException
     */
    public function fetchWeather(float $lat, float $lon): array
    {
        $url = config('services.open_meteo.weather_url');

        $response = Http::timeout(10)->get($url, [
            'latitude'              => $lat,
            'longitude'             => $lon,
            'current_weather'       => true,
            'hourly'                => 'relativehumidity_2m,windspeed_10m',
            'forecast_days'         => 1,
            'timezone'              => 'auto',
        ]);

        if ($response->status() === 429) {
            throw new RateLimitException("Rate limit exceeded for weather service.");
        }

        if ($response->failed()) {
            throw new ApiException(
                "Open-Meteo weather API returned HTTP {$response->status()}."
            );
        }

        $data           = $response->json();
        $currentWeather = $data['current_weather'] ?? [];

        $temperatureC = (float) ($currentWeather['temperature'] ?? 0);
        $temperatureF = round(($temperatureC * 9 / 5) + 32, 1);
        $wmoCode      = (int) ($currentWeather['weathercode'] ?? 0);
        $condition    = self::WMO_CODES[$wmoCode] ?? 'Unknown';
        $windSpeed    = (float) ($currentWeather['windspeed'] ?? 0);

        // Humidity comes from the first hourly value (current hour index 0)
        $humidity = (int) (($data['hourly']['relativehumidity_2m'][0] ?? 0));

        return [
            'temperature_c'  => $temperatureC,
            'temperature_f'  => $temperatureF,
            'condition'      => $condition,
            'humidity'       => $humidity,
            'wind_speed_kmh' => $windSpeed,
        ];
    }

    /**
     * Get current weather for a city, using the cache when available.
     * Weather data is cached for 10 minutes under the key "weather.{normalised_city}".
     *
     * @param  string  $city  City name (case-insensitive, whitespace-trimmed)
     * @return array{city: string, country: string, temperature_c: float, temperature_f: float, condition: string, humidity: int, wind_speed_kmh: float}
     *
     * @throws CityNotFoundException
     * @throws RateLimitException
     * @throws ApiException
     */
    public function getWeather(string $city): array
    {
        $normalised = strtolower(trim($city));
        $cacheKey   = "weather.{$normalised}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $location = $this->geocode($city);
        $weather  = $this->fetchWeather($location['lat'], $location['lon']);

        $result = array_merge([
            'city'    => $location['display_name'],
            'country' => strtoupper($location['country']),
        ], $weather);

        Cache::put($cacheKey, $result, now()->addMinutes(10));

        return $result;
    }
}
