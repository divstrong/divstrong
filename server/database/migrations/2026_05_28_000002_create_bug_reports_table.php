<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_reporter_site_id')->constrained()->cascadeOnDelete();
            $table->text('what_happened');
            $table->string('url', 2048);
            $table->string('reporter_email')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->unsignedSmallInteger('viewport_width')->nullable();
            $table->unsignedSmallInteger('viewport_height')->nullable();
            $table->json('console_errors')->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('status', 20)->default('new');
            $table->string('priority', 20)->default('normal');
            $table->ipAddress('ip_address')->nullable();
            $table->json('meta')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('bug_reporter_site_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
