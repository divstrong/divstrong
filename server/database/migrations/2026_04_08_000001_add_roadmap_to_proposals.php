<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('roadmap_enabled')->default(false)->after('discount_value');
            $table->string('roadmap_title')->nullable()->after('roadmap_enabled');
            $table->string('roadmap_subtitle')->nullable()->after('roadmap_title');
            $table->integer('roadmap_hours_per_sprint')->nullable()->after('roadmap_subtitle');
            $table->integer('roadmap_months')->nullable()->after('roadmap_hours_per_sprint');
        });

        Schema::create('proposal_roadmap_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('description')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->string('icon')->default('clipboard');
            $table->integer('duration_weeks')->default(4);
            $table->integer('hours')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_roadmap_phases');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'roadmap_enabled',
                'roadmap_title',
                'roadmap_subtitle',
                'roadmap_hours_per_sprint',
                'roadmap_months',
            ]);
        });
    }
};
