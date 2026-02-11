<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'DivStrong Admin',
            'email' => 'admin@divstrong.com',
            'password' => bcrypt('password'),
        ]);

        $this->call(LibrarySeeder::class);
    }
}
