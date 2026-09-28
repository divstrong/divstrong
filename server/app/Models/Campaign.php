<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named drip sequence. The steps carry the copy references and the delays; this row
 * carries the name and the rules that apply to the whole walk.
 */
class Campaign extends Model
{
    protected $fillable = [
        'name', 'key', 'description', 'is_active', 'stop_on_reply', 'stop_on_booking',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'stop_on_reply' => 'boolean',
        'stop_on_booking' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(CampaignStep::class)->orderBy('position');
    }

    public function activeSteps(): HasMany
    {
        return $this->steps()->where('is_active', true);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CampaignEnrollment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** The step that follows a position, or null at the end of the sequence. */
    public function stepAfter(?int $position): ?CampaignStep
    {
        return $this->activeSteps()
            ->when($position !== null, fn (Builder $q) => $q->where('position', '>', $position))
            ->orderBy('position')
            ->first();
    }
}
