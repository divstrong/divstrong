<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The third question on the preview page: how well the concept fits them, 1–5.
 *
 * The two yes/no answers say where they landed; this says by how much, which is what
 * separates "not quite" from "nowhere near" when deciding whether a redesign is worth it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->unsignedTinyInteger('fit_rating')->nullable()->after('interested');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('fit_rating');
        });
    }
};
