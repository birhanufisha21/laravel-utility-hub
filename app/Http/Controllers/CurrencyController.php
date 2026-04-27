<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\RateLimitException;
use App\Http\Requests\CurrencyConvertRequest;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CurrencyController extends Controller
{
    public function __construct(private readonly CurrencyService $currencyService)
    {
    }

    /**
     * Render the Currency Converter Blade view.
     */
    public function index()
    {
        return view('currency.index');
    }

    /**
     * Handle a currency conversion request.
     * POST /api/currency/convert
     */
    public function convert(CurrencyConvertRequest $request): JsonResponse
    {
        try {
            $result = $this->currencyService->convert(
                (float) $request->validated('amount'),
                $request->validated('from'),
                $request->validated('to')
            );

            return response()->json([
                'success'          => true,
                'from'             => $result['from'],
                'to'               => $result['to'],
                'amount'           => (float) $request->validated('amount'),
                'converted_amount' => $result['converted_amount'],
                'rate'             => $result['rate'],
            ]);
        } catch (RateLimitException $e) {
            Log::warning('Rate limit hit', [
                'service' => 'currency',
                'at'      => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests to the exchange rate service. Please try again later.',
                'code'    => 'RATE_LIMITED',
            ], 429);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The exchange rate service is currently unavailable.',
                'code'    => 'API_ERROR',
            ], 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in CurrencyController@convert', [
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
