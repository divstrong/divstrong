<?php

namespace App\Services;

use App\Exceptions\PayPalException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('paypal.base_url');
        $this->clientId = config('paypal.client_id');
        $this->clientSecret = config('paypal.client_secret');
    }

    public function getAccessToken(): string
    {
        return Cache::remember('paypal_access_token', 28800, function () {
            $response = Http::timeout(15)
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                Log::error('PayPal OAuth failed', ['body' => $response->body()]);
                throw new \RuntimeException('Failed to obtain PayPal access token');
            }

            return $response->json('access_token');
        });
    }

    public function createOrder(float $amount, string $currency, string $referenceId, string $description): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $referenceId,
                    'description' => $description,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
        ];

        $response = Http::timeout(15)
            ->withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/v2/checkout/orders", $payload);

        // Retry once on 401 (stale cached token)
        if ($response->status() === 401) {
            Cache::forget('paypal_access_token');
            $response = Http::timeout(15)
                ->withToken($this->getAccessToken())
                ->post("{$this->baseUrl}/v2/checkout/orders", $payload);
        }

        if ($response->failed()) {
            $exception = PayPalException::fromResponse('create order', $response);

            Log::error('PayPal create order failed', [
                'status' => $response->status(),
                'issue' => $exception->issue(),
                'debug_id' => $exception->debugId(),
                'body' => $response->body(),
            ]);

            throw $exception;
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        // The body has to be an empty JSON *object*. Http::post() with no data sends an
        // empty array, which serialises to `[]`, and PayPal rejects that as malformed.
        $capture = fn () => Http::timeout(15)
            ->withToken($this->getAccessToken())
            ->withHeaders(['Prefer' => 'return=representation'])
            ->withBody('{}', 'application/json')
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        $response = $capture();

        // Retry once on 401 (stale cached token)
        if ($response->status() === 401) {
            Cache::forget('paypal_access_token');
            $response = $capture();
        }

        if ($response->failed()) {
            $exception = PayPalException::fromResponse('capture', $response);

            Log::error('PayPal capture failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'issue' => $exception->issue(),
                'debug_id' => $exception->debugId(),
                'body' => $response->body(),
            ]);

            throw $exception;
        }

        return $response->json();
    }
}
