<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Investment quantities can be fractional — 1.5 sprints, 2.5 days. Existing whole numbers
 * carry over unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposal_cost_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('proposal_cost_items', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->change();
        });
    }
};
