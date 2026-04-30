<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('process_enabled')->default(true)->after('references_enabled');
            $table->string('process_eyebrow')->nullable()->after('process_enabled');
            $table->text('process_heading')->nullable()->after('process_eyebrow');
            $table->text('process_subheading')->nullable()->after('process_heading');
            $table->string('process_background')->nullable()->after('process_subheading');
            $table->json('process_stages')->nullable()->after('process_background');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'process_enabled',
                'process_eyebrow',
                'process_heading',
                'process_subheading',
                'process_background',
                'process_stages',
            ]);
        });
    }
};
