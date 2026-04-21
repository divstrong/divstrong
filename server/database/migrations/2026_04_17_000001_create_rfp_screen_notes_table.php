<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfp_screen_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfp_screen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();

            $table->index(['rfp_screen_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfp_screen_notes');
    }
};
