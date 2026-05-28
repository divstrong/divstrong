<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $admin = DB::table('roles')->where('name', 'Admin')->first();

        if ($admin) {
            $permissions = json_decode($admin->permissions, true) ?? [];

            if (! in_array('bug_reports', $permissions)) {
                $permissions[] = 'bug_reports';

                DB::table('roles')
                    ->where('id', $admin->id)
                    ->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    public function down(): void
    {
        $admin = DB::table('roles')->where('name', 'Admin')->first();

        if ($admin) {
            $permissions = json_decode($admin->permissions, true) ?? [];
            $permissions = array_values(array_filter($permissions, fn ($p) => $p !== 'bug_reports'));

            DB::table('roles')
                ->where('id', $admin->id)
                ->update(['permissions' => json_encode($permissions)]);
        }
    }
};
