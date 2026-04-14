<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsLibrary extends Model
{
    protected $table = 'terms_library';

    protected $fillable = ['content', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
