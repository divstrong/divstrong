<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfp_screen_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfp_screen_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_type', 20);
            $table->timestamps();

            $table->index('rfp_screen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfp_screen_attachments');
    }
};
