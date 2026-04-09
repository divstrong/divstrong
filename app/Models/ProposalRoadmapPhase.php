<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalRoadmapPhase extends Model
{
    protected $fillable = [
        'proposal_id',
        'title',
        'subtitle',
        'description',
        'color',
        'icon',
        'duration_weeks',
        'hours',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_weeks' => 'integer',
            'hours' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
