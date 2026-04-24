<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PortfolioItem extends Model
{
    protected $fillable = [
        'title', 'description', 'image', 'url', 'technologies',
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
        return $this->belongsToMany(Proposal::class, 'portfolio_item_proposal')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) return null;
        if (str_starts_with($this->image, 'images/')) {
            return asset($this->image);
        }
        return \Illuminate\Support\Facades\Storage::url($this->image);
    }
}
