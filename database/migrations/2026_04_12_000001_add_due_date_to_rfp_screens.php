<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('rfp_name');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropColumn('due_date');
        });
    }
};
