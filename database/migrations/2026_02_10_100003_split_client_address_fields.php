<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('address1')->nullable()->after('domain');
            $table->string('address2')->nullable()->after('address1');
            $table->string('city')->nullable()->after('address2');
            $table->string('state')->nullable()->after('city');
            $table->string('zip')->nullable()->after('state');
        });

        // Migrate existing address data into address1
        DB::table('clients')->whereNotNull('address')->update([
            'address1' => DB::raw('address'),
        ]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('address')->nullable()->after('domain');
        });

        DB::table('clients')->whereNotNull('address1')->update([
            'address' => DB::raw('address1'),
        ]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['address1', 'address2', 'city', 'state', 'zip']);
        });
    }
};
