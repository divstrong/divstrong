<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeamMember extends Model
{
    protected $fillable = [
        'name', 'title', 'avatar', 'description',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class, 'proposal_team_member')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) return null;
        if (str_starts_with($this->avatar, 'images/')) {
            return asset($this->avatar);
        }
        return \Illuminate\Support\Facades\Storage::url($this->avatar);
    }
}
