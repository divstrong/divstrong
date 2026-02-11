<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Cover page
            $table->date('proposal_date');
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_company')->nullable();
            $table->string('client_domain')->nullable();
            $table->string('project_title');
            $table->string('cover_image')->nullable();

            // Introduction
            $table->longText('introduction')->nullable();

            // Cost page
            $table->text('cost_notes')->nullable();
            $table->date('valid_until')->nullable();

            // Status
            $table->string('status')->default('draft');

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->integer('view_count')->default(0);

            // Acceptance
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('accepted_ip')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signature_name')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
