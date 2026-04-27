<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    /**
     * Fetch exchange rates from the external API.
     * Throws RateLimitException on HTTP 429, ApiException on any other failure.
     *
     * @param  string  $base  3-letter base currency code (e.g. "USD")
     * @return array<string, float>  Associative array of currency code => rate
     *
     * @throws RateLimitException
     * @throws ApiException
     */
    public function fetchFromApi(string $base): array
    {
        $baseUrl = config('services.exchange_rate.base_url');
        $key     = config('services.exchange_rate.key');

        $response = Http::timeout(10)->get("{$baseUrl}/{$key}/latest/{$base}");

        if ($response->status() === 429) {
            throw new RateLimitException("Rate limit exceeded for currency service (base: {$base}).");
        }

        if ($response->failed()) {
            throw new ApiException(
                "ExchangeRate-API returned HTTP {$response->status()} for base currency {$base}."
            );
        }

        $data = $response->json();

        if (($data['result'] ?? '') !== 'success' || empty($data['conversion_rates'])) {
            throw new ApiException("ExchangeRate-API returned an unexpected response structure.");
        }

        return $data['conversion_rates'];
    }

    /**
     * Get exchange rates for a base currency, using the cache when available.
     * Rates are cached for 60 minutes under the key "currency.rates.{BASE}".
     *
     * @param  string  $base  3-letter base currency code
     * @return array<string, float>
     *
     * @throws RateLimitException
     * @throws ApiException
     */
    public function getRates(string $base): array
    {
        $cacheKey = "currency.rates.{$base}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rates = $this->fetchFromApi($base);

        Cache::put($cacheKey, $rates, now()->addMinutes(60));

        return $rates;
    }

    /**
     * Convert an amount from one currency to another.
     *
     * @param  float   $amount  Positive numeric amount to convert
     * @param  string  $from    Source currency code (e.g. "USD")
     * @param  string  $to      Target currency code (e.g. "EUR")
     * @return array{converted_amount: float, rate: float, from: string, to: string}
     *
     * @throws RateLimitException
     * @throws ApiException
     * @throws \InvalidArgumentException  If the target currency is not in the rates map
     */
    public function convert(float $amount, string $from, string $to): array
    {
        $rates = $this->getRates($from);

        if (!isset($rates[$to])) {
            throw new \InvalidArgumentException("Currency code '{$to}' is not supported.");
        }

        $rate            = $rates[$to];
        $convertedAmount = round($amount * $rate, 2);

        return [
            'converted_amount' => $convertedAmount,
            'rate'             => $rate,
            'from'             => $from,
            'to'               => $to,
        ];
    }
}
