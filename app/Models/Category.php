<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public static function orderedOptions(): array
    {
        return static::orderBy('sort_order')
            ->pluck('name', 'name')
            ->toArray();
    }
}
