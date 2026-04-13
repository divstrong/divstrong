<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfpScreenAttachment extends Model
{
    protected $fillable = [
        'rfp_screen_id',
        'filename',
        'original_filename',
        'file_path',
        'file_type',
    ];

    public function rfpScreen(): BelongsTo
    {
        return $this->belongsTo(RfpScreen::class);
    }
}
