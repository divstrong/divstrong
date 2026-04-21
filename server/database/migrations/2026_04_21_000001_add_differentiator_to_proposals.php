<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('differentiator_enabled')->default(false)->after('overview_image');
            $table->text('differentiator_headline')->nullable()->after('differentiator_enabled');
            $table->string('differentiator_attribution')->nullable()->after('differentiator_headline');
            $table->string('differentiator_background')->nullable()->after('differentiator_attribution');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'differentiator_enabled',
                'differentiator_headline',
                'differentiator_attribution',
                'differentiator_background',
            ]);
        });
    }
};
