<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopeLibrary extends Model
{
    protected $table = 'scope_library';

    protected $fillable = ['category', 'title', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
