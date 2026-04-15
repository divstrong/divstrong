<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // Seed default roles
        $resources = [
            'dashboard',
            'clients',
            'proposals',
            'categories',
            'services',
            'scope_items',
            'settings',
        ];

        DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'permissions' => json_encode($resources),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Estimator',
                'permissions' => json_encode(['dashboard', 'clients', 'proposals', 'categories', 'services', 'scope_items']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Associate',
                'permissions' => json_encode(['dashboard', 'clients']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
