<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalPayment extends Model
{
    protected $fillable = [
        'proposal_id', 'milestone_id', 'paypal_order_id', 'paypal_capture_id',
        'amount', 'currency', 'status', 'paid_at', 'payer_email', 'paypal_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'paypal_response' => 'array',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProposalMilestone::class, 'milestone_id');
    }
}
