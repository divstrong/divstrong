<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->longText('change_request_content')->nullable()->after('cost_notes');
            $table->string('cr_signature_name')->nullable()->after('change_request_content');
            $table->longText('cr_signature_data')->nullable()->after('cr_signature_name');
            $table->timestamp('cr_signed_at')->nullable()->after('cr_signature_data');
            $table->string('tc_signature_name')->nullable()->after('cr_signed_at');
            $table->longText('tc_signature_data')->nullable()->after('tc_signature_name');
            $table->timestamp('tc_signed_at')->nullable()->after('tc_signature_data');
        });

        Schema::create('proposal_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_terms');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'change_request_content',
                'cr_signature_name', 'cr_signature_data', 'cr_signed_at',
                'tc_signature_name', 'tc_signature_data', 'tc_signed_at',
            ]);
        });
    }
};
