<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\ProposalTerm;

class Proposal extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'client_id', 'proposal_date', 'client_name', 'client_email',
        'client_company', 'client_domain', 'project_title', 'cover_image',
        'introduction', 'cost_notes', 'valid_until', 'status',
        'change_request_content', 'cr_signature_name', 'cr_signature_data', 'cr_signed_at',
        'tc_signature_name', 'tc_signature_data', 'tc_signed_at',
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
            'cr_signed_at' => 'datetime',
            'tc_signed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Proposal $proposal) {
            if (empty($proposal->uuid)) {
                do {
                    $code = strtoupper(Str::random(6));
                } while (static::where('uuid', $code)->exists());

                $proposal->uuid = $code;
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

    public function terms(): HasMany
    {
        return $this->hasMany(ProposalTerm::class)->orderBy('sort_order');
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
