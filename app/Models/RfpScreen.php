<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfpScreen extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'file_path',
        'file_type',
        'rfp_name',
        'prompt',
        'score',
        'summary',
        'red_flags',
        'requirements',
        'raw_response',
        'status',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'red_flags' => 'array',
            'requirements' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        if ($this->score >= 80) return 'success';
        if ($this->score >= 50) return 'warning';
        return 'danger';
    }

    public function getScoreLabelAttribute(): string
    {
        if ($this->score === null) return 'Pending';
        if ($this->score >= 80) return 'Good Fit';
        if ($this->score >= 50) return 'Maybe';
        return 'Poor Fit';
    }
}
