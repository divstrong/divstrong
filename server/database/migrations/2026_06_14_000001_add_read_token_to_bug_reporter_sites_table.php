<?php

use App\Models\BugReporterSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bug_reporter_sites', function (Blueprint $table) {
            $table->string('read_token', 64)->nullable()->unique()->after('public_key');
        });

        // Backfill a private read token for any sites that already exist so the
        // client-facing report link works without re-saving each tracker.
        BugReporterSite::whereNull('read_token')->get()->each(function (BugReporterSite $site) {
            $site->update(['read_token' => BugReporterSite::generateReadToken()]);
        });
    }

    public function down(): void
    {
        Schema::table('bug_reporter_sites', function (Blueprint $table) {
            $table->dropUnique(['read_token']);
            $table->dropColumn('read_token');
        });
    }
};
