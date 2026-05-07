<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->foreignId('proposal_id')->nullable()->after('user_id')
                ->constrained('proposals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proposal_id');
        });
    }
};
