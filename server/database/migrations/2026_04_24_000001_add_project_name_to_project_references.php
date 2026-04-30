<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_references', function (Blueprint $table) {
            $table->string('project_name')->nullable()->after('name');
            $table->string('project_location')->nullable()->after('project_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_references', function (Blueprint $table) {
            $table->dropColumn(['project_name', 'project_location']);
        });
    }
};
