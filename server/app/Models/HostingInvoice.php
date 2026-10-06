<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A renewal invoice for one hosting term, raised in PayPal. */
class HostingInvoice extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'hosting_account_id', 'term_start', 'term_end', 'due_date', 'amount', 'currency',
        'status', 'paypal_invoice_id', 'invoice_number', 'payment_url', 'recipients',
        'sent_at', 'paid_at', 'last_reminded_at', 'reminder_count', 'paypal_response',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'recipients' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'paypal_response' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class, 'hosting_account_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
