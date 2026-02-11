<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name', 'email', 'company', 'phone', 'domain',
        'address1', 'address2', 'city', 'state', 'zip',
        'notes',
    ];

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function clientNotes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }
}
