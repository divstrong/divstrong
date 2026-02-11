<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Proposal extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'client_id', 'proposal_date', 'client_name', 'client_email',
        'client_company', 'client_domain', 'project_title', 'cover_image',
        'introduction', 'cost_notes', 'valid_until', 'status',
        'sent_at', 'first_viewed_at', 'last_viewed_at', 'view_count',
        'accepted_at', 'declined_at', 'accepted_ip', 'signature_data', 'signature_name',
    ];

    protected function casts(): array
    {
        return [
            'proposal_date' => 'date',
            'valid_until' => 'date',
            'status' => ProposalStatus::class,
            'sent_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Proposal $proposal) {
            if (empty($proposal->uuid)) {
                $proposal->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeItems(): HasMany
    {
        return $this->hasMany(ProposalScopeItem::class)->orderBy('sort_order');
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(ProposalCostItem::class)->orderBy('sort_order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProposalMilestone::class)->orderBy('sort_order');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->costItems->sum('amount');
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal;
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/proposal/{$this->uuid}");
    }
}
