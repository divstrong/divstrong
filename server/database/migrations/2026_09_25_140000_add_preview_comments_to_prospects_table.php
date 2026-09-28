<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What they typed when they said no.
 *
 * A yes/no tells you the outcome; this is the only part that tells you why, which is the
 * half worth acting on. Kept on the prospect rather than only in the activity timeline so
 * it can be read at a glance beside their answers, and so a list of "what people disliked"
 * is one query rather than a scan of JSON payloads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->text('preview_comments')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('preview_comments');
        });
    }
};
