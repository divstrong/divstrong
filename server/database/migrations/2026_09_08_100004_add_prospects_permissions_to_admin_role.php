<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Grant the Admin role the two new permission keys.
 *
 * Without this the Prospects resource is invisible to everyone with a role attached, because
 * canAccess() answers false for any key the role does not hold — a user with no role at all
 * is treated as super admin and would see it either way, which is exactly the sort of split
 * that makes a missing feature look like a broken one.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['prospects', 'email_templates'];

    public function up(): void
    {
        $admin = DB::table('roles')->where('name', 'Admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = json_decode($admin->permissions, true) ?? [];

        foreach (self::PERMISSIONS as $permission) {
            if (! in_array($permission, $permissions, true)) {
                $permissions[] = $permission;
            }
        }

        DB::table('roles')
            ->where('id', $admin->id)
            ->update(['permissions' => json_encode(array_values($permissions))]);
    }

    public function down(): void
    {
        $admin = DB::table('roles')->where('name', 'Admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = json_decode($admin->permissions, true) ?? [];

        $permissions = array_values(array_filter(
            $permissions,
            fn ($p) => ! in_array($p, self::PERMISSIONS, true),
        ));

        DB::table('roles')
            ->where('id', $admin->id)
            ->update(['permissions' => json_encode($permissions)]);
    }
};
