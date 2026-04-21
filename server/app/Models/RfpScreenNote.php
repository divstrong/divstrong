<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfpScreenNote extends Model
{
    protected $fillable = [
        'rfp_screen_id',
        'user_id',
        'body',
        'attachment_path',
        'attachment_name',
    ];

    public function rfpScreen(): BelongsTo
    {
        return $this->belongsTo(RfpScreen::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
