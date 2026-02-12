<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopeLibrary extends Model
{
    protected $table = 'scope_library';

    protected $fillable = ['category', 'title', 'description', 'bullets', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
