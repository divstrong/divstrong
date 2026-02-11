<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_library', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('name');
            $table->decimal('default_price', 10, 2);
            $table->string('unit')->default('fixed');
            $table->integer('default_quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_library');
    }
};
