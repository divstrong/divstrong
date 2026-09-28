<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * A failed PayPal REST call, with the response kept intact.
 *
 * PayPal answers failures with an issue code, and the code is the only part worth
 * acting on: the accompanying message is written for whoever integrated the API, not
 * for the person holding the card. userMessage() turns the code into something a
 * client can act on, and anything unrecognised falls back to wording that doesn't
 * pretend to know what went wrong.
 */
class PayPalException extends RuntimeException
{
    /**
     * Only these reach the payer. Anything absent from this list is a fault on our
     * side or PayPal's, and saying so plainly beats leaking API wording.
     */
    private const MESSAGES = [
        'INSTRUMENT_DECLINED' => 'Your card was declined. Try another card, or pay with your PayPal balance.',
        'PAYER_CANNOT_PAY' => 'This payment method can\'t be used for this purchase. Try another card or pay with PayPal.',
        'PAYER_ACCOUNT_RESTRICTED' => 'Your PayPal account is restricted and can\'t complete this payment. Contact PayPal, or try a card instead.',
        'PAYER_ACTION_REQUIRED' => 'Your bank needs to confirm this payment. Complete the confirmation, then try again.',
        'PAYER_ACCOUNT_LOCKED_OR_CLOSED' => 'That PayPal account can\'t be used. Try another account or pay by card.',
        'TRANSACTION_REFUSED' => 'PayPal refused this transaction. Try another payment method.',
        'CARD_EXPIRED' => 'That card has expired. Try another card.',
        'INVALID_SECURITY_CODE' => 'That security code doesn\'t match the card. Check the CVV and try again.',
        'ORDER_ALREADY_CAPTURED' => 'This payment has already gone through. Refresh the page to see it.',
        'ORDER_NOT_APPROVED' => 'The payment wasn\'t approved. Start the payment again.',
        'ORDER_EXPIRED' => 'This payment session expired. Start the payment again.',
        'DUPLICATE_INVOICE_ID' => 'This payment has already been submitted. Refresh the page before trying again.',
    ];

    public function __construct(
        string $message,
        public readonly array $payload = [],
        public readonly int $status = 0,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(string $action, Response $response): self
    {
        $payload = is_array($decoded = $response->json()) ? $decoded : [];

        return new self(
            sprintf('PayPal %s failed (%d): %s', $action, $response->status(), $response->body()),
            $payload,
            $response->status(),
        );
    }

    /** PayPal reports the specific problem on the detail, and the category on the body. */
    public function issue(): ?string
    {
        return $this->payload['details'][0]['issue'] ?? $this->payload['name'] ?? null;
    }

    /** PayPal's own correlation id — the one thing their support asks for. */
    public function debugId(): ?string
    {
        return $this->payload['debug_id'] ?? null;
    }

    public function userMessage(): string
    {
        return self::MESSAGES[$this->issue()]
            ?? 'We couldn\'t complete this payment. Your card has not been charged — please try again, or contact us and we\'ll sort it out.';
    }

    /** True when trying the same thing again could plausibly work. */
    public function isRetryable(): bool
    {
        return ! in_array($this->issue(), ['ORDER_ALREADY_CAPTURED', 'DUPLICATE_INVOICE_ID'], true);
    }
}
