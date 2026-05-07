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
        'proposal_id',
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
        'scanned_with_model',
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

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
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

    public function getScannedWithModelLabelAttribute(): ?string
    {
        $id = $this->scanned_with_model;
        if (! $id) return null;

        return match (true) {
            str_contains($id, 'opus-4-7') => 'Opus 4.7',
            str_contains($id, 'opus-4-6') => 'Opus 4.6',
            str_contains($id, 'opus-4') => 'Opus 4',
            str_contains($id, 'sonnet-4-6') => 'Sonnet 4.6',
            str_contains($id, 'sonnet-4-5') => 'Sonnet 4.5',
            str_contains($id, 'sonnet-4') => 'Sonnet 4',
            str_contains($id, 'haiku-4-5') => 'Haiku 4.5',
            str_contains($id, 'haiku-4') => 'Haiku 4',
            str_contains($id, 'opus-3') => 'Opus 3',
            str_contains($id, 'sonnet-3') => 'Sonnet 3',
            str_contains($id, 'haiku-3') => 'Haiku 3',
            default => $id,
        };
    }
}
