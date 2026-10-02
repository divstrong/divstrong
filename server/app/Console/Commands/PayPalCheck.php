<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Says which PayPal environment this server is really using, and whether its keys work there.
 *
 * Reads the loaded config rather than .env, because the two disagree whenever the config is
 * cached and .env was edited afterwards — which is the usual reason a switch to live "did
 * not take". The keys are tested with a fresh token request, never the cached token, so a
 * leftover sandbox token cannot make bad live keys look fine.
 */
class PayPalCheck extends Command
{
    protected $signature = 'paypal:check';

    protected $description = 'Show the PayPal mode in use and verify the credentials against it';

    public function handle(): int
    {
        $mode = (string) config('paypal.mode');
        $base = (string) config('paypal.base_url');
        $clientId = (string) config('paypal.client_id');
        $live = $mode === 'live' && str_contains($base, 'api-m.paypal.com');

        $this->line('Mode:        ' . ($live ? 'LIVE' : 'SANDBOX (test — no real money)') . "  [PAYPAL_MODE={$mode}]");
        $this->line('API:         ' . $base);
        $this->line('Checkout JS: ' . config('paypal.sdk_url'));
        $this->line('Client ID:   ' . ($clientId !== '' ? substr($clientId, 0, 6) . '…' . substr($clientId, -4) : '(not set)'));
        $this->line('Config:      ' . (app()->configurationIsCached() ? 'cached — run "optimize" after editing .env' : 'read from .env'));

        if ($clientId === '' || blank(config('paypal.client_secret'))) {
            $this->error('Client ID or secret is missing.');

            return self::FAILURE;
        }

        $response = Http::timeout(15)->asForm()
            ->withBasicAuth($clientId, (string) config('paypal.client_secret'))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            $this->error('Keys REJECTED by ' . ($live ? 'live' : 'sandbox') . ' PayPal: '
                . $response->status() . ' ' . ($response->json('error_description') ?? $response->json('error') ?? ''));
            $this->line('Sandbox keys do not work in live mode and vice versa — the mode and the keys must match.');

            return self::FAILURE;
        }

        // A token cached before the switch belongs to the old environment.
        Cache::forget('paypal_access_token');

        $this->info('Keys ACCEPTED by ' . ($live ? 'live' : 'sandbox') . ' PayPal'
            . ($response->json('app_id') ? ' (app ' . $response->json('app_id') . ')' : '') . '.');

        if (! $live) {
            $this->warn('This server is in SANDBOX mode: payments here are tests and move no money.');
        }

        return self::SUCCESS;
    }
}
