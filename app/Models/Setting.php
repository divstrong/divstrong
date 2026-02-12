<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name', 'email', 'phone',
        'address1', 'address2', 'city', 'state', 'zip',
        'logo',
    ];

    public static function instance(): static
    {
        return static::firstOrCreate([]);
    }
}
