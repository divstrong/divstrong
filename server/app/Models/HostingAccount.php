<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One hosted site and the term it is paid up to.
 *
 * Renewals run one year at a time: the next term starts the day after the current one ends,
 * and paying its invoice rolls term_start/term_end forward to it.
 */
class HostingAccount extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'client_id', 'name', 'domain', 'billing_email',
        'term_start', 'term_end', 'monthly_rate', 'term_amount',
        'status', 'auto_renew', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
            'monthly_rate' => 'decimal:2',
            'term_amount' => 'decimal:2',
            'auto_renew' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(HostingInvoice::class)->latest('term_start');
    }

    /** The invoice for the term that follows the current one, if it has been raised. */
    public function renewalInvoice(): HasOne
    {
        return $this->hasOne(HostingInvoice::class)->ofMany('term_start', 'max');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /** First day of the next term. */
    public function nextTermStart(): Carbon
    {
        return $this->term_end->copy()->addDay();
    }

    /**
     * Last day of the next term: a year from its start, less a day. Overflow is wanted here —
     * a term starting Feb 29 runs to Feb 28, and one starting Mar 1 before a leap day ends Feb 29.
     */
    public function nextTermEnd(): Carbon
    {
        return $this->nextTermStart()->addYear()->subDay();
    }

    public function daysUntilDue(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->term_end, false);
    }

    /** The invoice for the next term, open or paid — null until one is raised. */
    public function currentRenewal(): ?HostingInvoice
    {
        $invoice = $this->relationLoaded('renewalInvoice') ? $this->renewalInvoice : $this->renewalInvoice()->first();

        return $invoice && $invoice->term_start->isSameDay($this->nextTermStart()) ? $invoice : null;
    }

    /** Where invoices go: the account's own billing address, else the client's. */
    public function billingEmails(): array
    {
        return array_values(array_filter([$this->billing_email ?: $this->client?->email]));
    }
}
