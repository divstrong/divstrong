<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('feature_image')->nullable();
            $table->string('feature_title')->nullable();
            $table->text('feature_description')->nullable();
            $table->json('feature_tags')->nullable();
            $table->string('feature_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'feature_image',
                'feature_title',
                'feature_description',
                'feature_tags',
                'feature_url',
            ]);
        });
    }
};
