<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_references', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('project_description')->nullable();
            $table->unsignedSmallInteger('year_completed')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('proposal_project_reference', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_reference_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['proposal_id', 'project_reference_id'], 'ppr_unique');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('references_enabled')->default(false)->after('performance_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('references_enabled');
        });

        Schema::dropIfExists('proposal_project_reference');
        Schema::dropIfExists('project_references');
    }
};
