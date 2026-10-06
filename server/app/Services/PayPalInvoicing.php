<?php

namespace App\Services;

use App\Exceptions\PayPalException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Invoicing API v2 — the hosting renewals' side of PayPal.
 *
 * Proposals take payment through Checkout orders on our own page; renewals are real PayPal
 * invoices instead, so the client gets a proper invoice record, can pay by card or PayPal
 * from PayPal's own page, and the invoice sits in the PayPal account with everything else.
 *
 * Invoices are sent with PayPal's own email switched off: the client hears from us, in our
 * branded notice, and the button in it opens PayPal's payer page.
 */
class PayPalInvoicing
{
    public function __construct(private PayPalService $paypal) {}

    /**
     * Create a draft invoice and return its PayPal id and number.
     *
     * @param  array<int, string>  $emails  first is the primary recipient, the rest are copied
     * @return array{id: string, number: string, response: array}
     */
    public function create(array $invoice, array $emails): array
    {
        // No invoice_number sent: PayPal assigns the next one in the account's own sequence.
        $recipients = collect($emails)->values()->map(fn (string $email, int $i) => $i === 0
            ? ['billing_info' => array_filter([
                'business_name' => $invoice['business_name'] ?? null,
                'email_address' => $email,
            ])]
            : null)->filter()->values()->all();

        $payload = [
            'detail' => [
                'reference' => $invoice['reference'] ?? null,
                'invoice_date' => now()->toDateString(),
                'currency_code' => $invoice['currency'] ?? 'USD',
                'note' => $invoice['note'] ?? null,
                'term' => $invoice['term'] ?? null,
                'payment_term' => ['due_date' => $invoice['due_date']],
            ],
            // Sent in full every time: the API does not fall back to the business profile
            // saved in PayPal, so anything left out here is simply missing from the invoice.
            'invoicer' => [
                'business_name' => config('hosting.business_name'),
                'address' => array_filter((array) config('hosting.address')),
                'website' => config('hosting.website'),
                'logo_url' => config('hosting.logo_url'),
            ],
            'primary_recipients' => $recipients,
            'additional_recipients' => collect($emails)->slice(1)
                ->map(fn (string $email) => ['email_address' => $email])->values()->all(),
            'items' => [[
                'name' => $invoice['item_name'],
                'description' => $invoice['item_description'] ?? null,
                'quantity' => '1',
                'unit_amount' => [
                    'currency_code' => $invoice['currency'] ?? 'USD',
                    'value' => number_format((float) $invoice['amount'], 2, '.', ''),
                ],
                'unit_of_measure' => 'AMOUNT',
            ]],
            'configuration' => [
                'allow_tip' => false,
                'tax_calculated_after_discount' => true,
                'tax_inclusive' => false,
            ],
        ];

        $response = $this->request(fn (PendingRequest $http) => $http
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->url('/v2/invoicing/invoices'), $this->clean($payload)), 'create invoice');

        $json = $response->json() ?? [];

        // With return=representation the id is in the body; without it, only in the link.
        $id = $json['id'] ?? basename((string) ($json['href'] ?? ''));
        $number = $json['detail']['invoice_number'] ?? ($this->get($id)['detail']['invoice_number'] ?? '');

        return ['id' => $id, 'number' => $number, 'response' => $json];
    }

    /**
     * Mark the invoice sent without PayPal emailing anyone, and return the payer link.
     */
    public function send(string $id): string
    {
        $response = $this->request(fn (PendingRequest $http) => $http
            ->post($this->url("/v2/invoicing/invoices/{$id}/send"), [
                'send_to_invoicer' => false,
                'send_to_recipient' => false,
            ]), 'send invoice');

        $href = $response->json('href');

        // Some responses carry only a status; the payer link is always on the invoice itself.
        return $href && str_contains($href, '/invoice/') ? $href : $this->payerUrl($id);
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        return $this->request(fn (PendingRequest $http) => $http
            ->get($this->url("/v2/invoicing/invoices/{$id}")), 'fetch invoice')->json() ?? [];
    }

    public function payerUrl(string $id): string
    {
        return (string) ($this->get($id)['detail']['metadata']['recipient_view_url'] ?? '');
    }

    public function cancel(string $id): void
    {
        $this->request(fn (PendingRequest $http) => $http
            ->post($this->url("/v2/invoicing/invoices/{$id}/cancel"), [
                'send_to_invoicer' => false,
                'send_to_recipient' => false,
            ]), 'cancel invoice');
    }

    private function url(string $path): string
    {
        return rtrim((string) config('paypal.base_url'), '/') . $path;
    }

    /** One call, retried once with a fresh token on 401, as PayPalService does. */
    private function request(\Closure $call, string $action): Response
    {
        $send = fn () => $call(Http::timeout(20)->acceptJson()->withToken($this->paypal->getAccessToken()));

        $response = $send();

        if ($response->status() === 401) {
            Cache::forget('paypal_access_token');
            $response = $send();
        }

        if ($response->failed()) {
            $exception = PayPalException::fromResponse($action, $response);

            Log::error("PayPal {$action} failed", [
                'status' => $response->status(),
                'issue' => $exception->issue(),
                'debug_id' => $exception->debugId(),
                'body' => $response->body(),
            ]);

            throw $exception;
        }

        return $response;
    }

    /** PayPal rejects nulls and empty arrays in places; drop them. */
    private function clean(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->clean($item);
            }

            if ($item === null || $item === []) {
                unset($value[$key]);
            } else {
                $value[$key] = $item;
            }
        }

        return $value;
    }
}
