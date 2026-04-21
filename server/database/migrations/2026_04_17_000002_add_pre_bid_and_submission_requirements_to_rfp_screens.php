<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->date('pre_bid_conference_date')->nullable()->after('due_date');
            $table->text('pre_bid_conference_details')->nullable()->after('pre_bid_conference_date');
            $table->json('submission_requirements')->nullable()->after('requirements');
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropColumn([
                'pre_bid_conference_date',
                'pre_bid_conference_details',
                'submission_requirements',
            ]);
        });
    }
};
