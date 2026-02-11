<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalScopeItem extends Model
{
    protected $fillable = ['proposal_id', 'category', 'title', 'description', 'sort_order'];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
