<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reporter_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('domain');
            $table->string('public_key', 64)->unique();
            $table->string('notify_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('client_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reporter_sites');
    }
};
