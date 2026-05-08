<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->string('target_department')->nullable()->after('locality_county');
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropColumn('target_department');
        });
    }
};
