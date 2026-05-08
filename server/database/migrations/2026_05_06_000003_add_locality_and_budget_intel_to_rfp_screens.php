<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->string('locality_city')->nullable()->after('contact_phone');
            $table->string('locality_state')->nullable()->after('locality_city');
            $table->string('locality_county')->nullable()->after('locality_state');

            $table->json('budget_intel')->nullable()->after('scanned_with_model');
            $table->timestamp('budget_intel_at')->nullable()->after('budget_intel');
            $table->string('budget_intel_model')->nullable()->after('budget_intel_at');
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropColumn([
                'locality_city',
                'locality_state',
                'locality_county',
                'budget_intel',
                'budget_intel_at',
                'budget_intel_model',
            ]);
        });
    }
};
