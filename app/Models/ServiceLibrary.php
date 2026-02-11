<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLibrary extends Model
{
    protected $table = 'service_library';

    protected $fillable = ['category', 'name', 'default_price', 'unit', 'default_quantity', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
