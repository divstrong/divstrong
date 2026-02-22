<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public static function resourceOptions(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'clients' => 'Clients',
            'proposals' => 'Proposals',
            'categories' => 'Categories',
            'services' => 'Services',
            'scope_items' => 'Scope Items',
            'settings' => 'Settings',
        ];
    }

    public function hasPermission(string $resource): bool
    {
        return in_array($resource, $this->permissions ?? []);
    }
}
