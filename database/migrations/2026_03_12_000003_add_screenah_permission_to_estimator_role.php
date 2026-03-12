<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $estimator = DB::table('roles')->where('name', 'Estimator')->first();

        if ($estimator) {
            $permissions = json_decode($estimator->permissions, true) ?? [];

            if (! in_array('screenah', $permissions)) {
                $permissions[] = 'screenah';

                DB::table('roles')
                    ->where('id', $estimator->id)
                    ->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    public function down(): void
    {
        $estimator = DB::table('roles')->where('name', 'Estimator')->first();

        if ($estimator) {
            $permissions = json_decode($estimator->permissions, true) ?? [];
            $permissions = array_values(array_filter($permissions, fn ($p) => $p !== 'screenah'));

            DB::table('roles')
                ->where('id', $estimator->id)
                ->update(['permissions' => json_encode($permissions)]);
        }
    }
};
