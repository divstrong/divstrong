<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectReference extends Model
{
    protected $fillable = [
        'name', 'title', 'company', 'email', 'phone',
        'project_description', 'year_completed',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'year_completed' => 'integer',
        ];
    }

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class, 'proposal_project_reference')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
