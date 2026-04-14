<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('rfp_name');
            $table->string('contact_title')->nullable()->after('contact_name');
            $table->string('contact_department')->nullable()->after('contact_title');
            $table->string('contact_email')->nullable()->after('contact_department');
            $table->string('contact_phone')->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('rfp_screens', function (Blueprint $table) {
            $table->dropColumn([
                'contact_name',
                'contact_title',
                'contact_department',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
