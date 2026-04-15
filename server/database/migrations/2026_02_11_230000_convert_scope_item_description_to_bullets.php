<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add bullets column to proposal_scope_items
        Schema::table('proposal_scope_items', function (Blueprint $table) {
            $table->json('bullets')->nullable()->after('title');
        });

        // Migrate existing description data to bullets
        DB::table('proposal_scope_items')->whereNotNull('description')->eachById(function ($item) {
            DB::table('proposal_scope_items')
                ->where('id', $item->id)
                ->update(['bullets' => json_encode([$item->description])]);
        });

        // Drop description column
        Schema::table('proposal_scope_items', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Add bullets column to scope_library
        Schema::table('scope_library', function (Blueprint $table) {
            $table->json('bullets')->nullable()->after('title');
        });

        // Migrate existing description data to bullets
        DB::table('scope_library')->whereNotNull('description')->eachById(function ($item) {
            DB::table('scope_library')
                ->where('id', $item->id)
                ->update(['bullets' => json_encode([$item->description])]);
        });

        // Drop description column
        Schema::table('scope_library', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_scope_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });

        DB::table('proposal_scope_items')->whereNotNull('bullets')->eachById(function ($item) {
            $bullets = json_decode($item->bullets, true);
            if (! empty($bullets)) {
                DB::table('proposal_scope_items')
                    ->where('id', $item->id)
                    ->update(['description' => $bullets[0]]);
            }
        });

        Schema::table('proposal_scope_items', function (Blueprint $table) {
            $table->dropColumn('bullets');
        });

        Schema::table('scope_library', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });

        DB::table('scope_library')->whereNotNull('bullets')->eachById(function ($item) {
            $bullets = json_decode($item->bullets, true);
            if (! empty($bullets)) {
                DB::table('scope_library')
                    ->where('id', $item->id)
                    ->update(['description' => $bullets[0]]);
            }
        });

        Schema::table('scope_library', function (Blueprint $table) {
            $table->dropColumn('bullets');
        });
    }
};
