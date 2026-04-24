<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('investment_enabled')->default(true)->after('about_enabled');
            $table->boolean('milestones_enabled')->default(true)->after('investment_enabled');
            $table->boolean('changes_enabled')->default(true)->after('milestones_enabled');
            $table->boolean('terms_enabled')->default(true)->after('changes_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'investment_enabled',
                'milestones_enabled',
                'changes_enabled',
                'terms_enabled',
            ]);
        });
    }
};
