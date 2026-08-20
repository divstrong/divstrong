<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->default(175)->after('logo');
            $table->decimal('daily_rate', 10, 2)->default(1000)->after('hourly_rate');
            $table->decimal('sprint_rate', 10, 2)->default(3000)->after('daily_rate');
            $table->unsignedInteger('hours_per_day')->default(10)->after('sprint_rate');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['hourly_rate', 'daily_rate', 'sprint_rate', 'hours_per_day']);
        });
    }
};
