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
        // The first address is the one billed, with whatever we know about them — name,
        // company, postal address — so the invoice reads "Bill to: Ryan Leach, Fresh Move
        // Media, …" rather than a bare email. The rest are copied.
        $recipients = [[
            'billing_info' => array_filter([
                'name' => $this->personName($invoice['contact_name'] ?? null),
                'business_name' => $invoice['business_name'] ?? null,
                'email_address' => $emails[0] ?? null,
                'address' => $this->address($invoice['address'] ?? null),
            ]),
        ]];

        $payload = [
            'detail' => [
                'reference' => $invoice['reference'] ?? null,
                'invoice_date' => now()->toDateString(),
                'currency_code' => $invoice['currency'] ?? 'USD',
                'note' => $invoice['note'] ?? null,
                'term' => $invoice['term'] ?? null,
                // A date when there is one (hosting renewals); otherwise due on receipt.
                'payment_term' => filled($invoice['due_date'] ?? null)
                    ? ['due_date' => $invoice['due_date']]
                    : ['term_type' => 'DUE_ON_RECEIPT'],
            ],
            // Sent in full every time: the API does not fall back to the business profile
            // saved in PayPal, so anything left out here is simply missing from the invoice.
            'invoicer' => [
                'business_name' => config('hosting.business_name'),
                // Must be an email confirmed on the PayPal account, or PayPal refuses the invoice.
                'email_address' => config('hosting.invoicer_email') ?: null,
                'address' => array_filter((array) config('hosting.address')),
                'phones' => filled(config('hosting.phone'))
                    ? [['country_code' => '1', 'national_number' => preg_replace('/\D/', '', (string) config('hosting.phone')), 'phone_type' => 'MOBILE']]
                    : null,
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
     * Send the invoice and return the payer link.
     *
     * By default PayPal emails nobody — the hosting renewals go out in our own branded notice.
     * Pass $emailRecipient to have PayPal deliver its own invoice email instead.
     */
    public function send(string $id, bool $emailRecipient = false): string
    {
        $response = $this->request(fn (PendingRequest $http) => $http
            ->post($this->url("/v2/invoicing/invoices/{$id}/send"), [
                'send_to_invoicer' => false,
                'send_to_recipient' => $emailRecipient,
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

    /** "Ryan Leach" → {given_name: Ryan, surname: Leach}; PayPal wants the parts. */
    private function personName(?string $name): ?array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $name, 2);

        return array_filter(['given_name' => $parts[0], 'surname' => $parts[1] ?? null]);
    }

    /**
     * A postal address from loose parts. Client records do not always split it into fields —
     * sometimes the whole thing sits in the first line — so lines are joined rather than
     * guessed at, and nothing is sent at all without a first line.
     *
     * @param  array{line1?: ?string, line2?: ?string, city?: ?string, state?: ?string, zip?: ?string}|null  $address
     */
    private function address(?array $address): ?array
    {
        $line1 = trim(preg_replace('/\s*[\r\n]+\s*/', ', ', (string) ($address['line1'] ?? '')));

        if ($line1 === '') {
            return null;
        }

        return array_filter([
            'address_line_1' => mb_substr($line1, 0, 300),
            'address_line_2' => filled($address['line2'] ?? null) ? mb_substr(trim($address['line2']), 0, 300) : null,
            'admin_area_2' => $address['city'] ?? null,
            'admin_area_1' => $address['state'] ?? null,
            'postal_code' => $address['zip'] ?? null,
            'country_code' => 'US',
        ]);
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
