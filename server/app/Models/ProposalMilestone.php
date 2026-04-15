<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalMilestone extends Model
{
    protected $fillable = [
        'proposal_id', 'title', 'description', 'percentage', 'amount',
        'due_description', 'sort_order', 'payment_status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProposalPayment::class, 'milestone_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
