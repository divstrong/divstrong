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

        $secret = (string) config('paypal.client_secret');

        // Copy-paste damage: quotes or whitespace inside the value are sent to PayPal as part
        // of the key. Raw .env is checked too, since config() may already have trimmed it.
        foreach (['PAYPAL_CLIENT_ID' => $clientId, 'PAYPAL_CLIENT_SECRET' => $secret] as $name => $value) {
            $raw = $this->rawEnv($name);
            $this->line(str_pad($name . ':', 22) . strlen($value) . ' chars'
                . ($raw !== null && $raw !== trim($raw, " \t\"'") ? '  ⚠ .env value has quotes/spaces around it' : '')
                . (preg_match('/\s/', $value) ? '  ⚠ contains whitespace' : ''));
        }

        $response = $this->token($base, $clientId, $secret);

        if ($response->failed()) {
            $this->error('Keys REJECTED by ' . ($live ? 'live' : 'sandbox') . ' PayPal: '
                . $response->status() . ' ' . ($response->json('error_description') ?? $response->json('error') ?? ''));

            // Try the other environment: tells "wrong kind of keys" apart from "wrong keys".
            $otherBase = $live ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

            if ($this->token($otherBase, $clientId, $secret)->successful()) {
                $this->warn('These keys DO work on ' . ($live ? 'sandbox' : 'live') . ' PayPal — they are '
                    . ($live ? 'sandbox' : 'live') . ' keys. Copy the ' . ($live ? 'Live' : 'Sandbox')
                    . ' app\'s Client ID and Secret from developer.paypal.com (toggle at the top).');
            } else {
                $this->warn('They fail on both live and sandbox, so the ID and secret do not belong together.');
                $this->line('In developer.paypal.com → Apps & Credentials → Live, open the app and copy BOTH');
                $this->line('the Client ID and the Secret from that same app (secrets are not shared across apps).');
            }

            return self::FAILURE;
        }

        // A token cached before the switch belongs to the old environment.
        Cache::forget('paypal_access_token');

        $this->info('Keys ACCEPTED by ' . ($live ? 'live' : 'sandbox') . ' PayPal'
            . ($response->json('app_id') ? ' (app ' . $response->json('app_id') . ')' : '') . '.');

        // Hosting renewals use the Invoicing API, which is a separate feature on the PayPal app.
        $invoicing = str_contains((string) $response->json('scope'), 'uri.paypal.com/services/invoicing');
        $invoicing
            ? $this->info('Invoicing:   enabled (hosting renewal invoices will work).')
            : $this->warn('Invoicing:   NOT enabled — hosting renewal invoices will fail. In developer.paypal.com → Apps & Credentials → this app, switch on Invoicing.');

        if (! $live) {
            $this->warn('This server is in SANDBOX mode: payments here are tests and move no money.');
        }

        return self::SUCCESS;
    }

    private function token(string $base, string $clientId, string $secret): \Illuminate\Http\Client\Response
    {
        return Http::timeout(15)->asForm()
            ->withBasicAuth($clientId, $secret)
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials']);
    }

    /** The value exactly as written in .env, or null if it is not there. */
    private function rawEnv(string $name): ?string
    {
        $path = base_path('.env');

        if (! is_readable($path)) {
            return null;
        }

        $matches = preg_grep('/^\s*' . preg_quote($name, '/') . '\s*=/', file($path, FILE_IGNORE_NEW_LINES) ?: []);

        if (count($matches) > 1) {
            $this->warn("{$name} is defined " . count($matches) . ' times in .env — only the first one is used.');
        }

        $line = reset($matches);

        return $line === false ? null : substr($line, strpos($line, '=') + 1);
    }
}
