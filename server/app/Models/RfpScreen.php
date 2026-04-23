<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfpScreen extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'file_path',
        'file_type',
        'rfp_name',
        'contact_name',
        'contact_title',
        'contact_department',
        'contact_email',
        'contact_phone',
        'due_date',
        'pre_bid_conference_date',
        'pre_bid_conference_details',
        'prompt',
        'score',
        'summary',
        'red_flags',
        'requirements',
        'submission_requirements',
        'raw_response',
        'status',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'red_flags' => 'array',
            'requirements' => 'array',
            'submission_requirements' => 'array',
            'analyzed_at' => 'datetime',
            'due_date' => 'date',
            'pre_bid_conference_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RfpScreenAttachment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RfpScreenNote::class);
    }

    public function scopeForUser(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user && $user->role) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function getScoreBadgeColorAttribute(): string
    {
        if ($this->score === null) return 'gray';
        if ($this->score >= 75) return 'success';
        if ($this->score >= 60) return 'warning';
        return 'danger';
    }

    public function getScoreLabelAttribute(): string
    {
        if ($this->score === null) return 'Pending';
        if ($this->score >= 75) return 'Great Fit';
        if ($this->score >= 60) return 'Good Fit';
        return 'Not Us';
    }
}
