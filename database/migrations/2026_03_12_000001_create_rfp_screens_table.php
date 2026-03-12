<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfp_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_type', 20);
            $table->text('prompt');
            $table->integer('score')->nullable();
            $table->text('summary')->nullable();
            $table->json('red_flags')->nullable();
            $table->json('requirements')->nullable();
            $table->text('raw_response')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfp_screens');
    }
};
