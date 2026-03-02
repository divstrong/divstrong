<?php

namespace App\Services;

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
            Log::error('PayPal create order failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Failed to create PayPal order: ' . $response->body());
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        $response = Http::timeout(15)
            ->withToken($this->getAccessToken())
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        // Retry once on 401 (stale cached token)
        if ($response->status() === 401) {
            Cache::forget('paypal_access_token');
            $response = Http::timeout(15)
                ->withToken($this->getAccessToken())
                ->withHeaders(['Prefer' => 'return=representation'])
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");
        }

        if ($response->failed()) {
            Log::error('PayPal capture failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to capture PayPal payment: ' . $response->body());
        }

        return $response->json();
    }
}
