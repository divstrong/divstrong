<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->string('scanned_with_model')->nullable()->after('analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropColumn('scanned_with_model');
        });
    }
};
