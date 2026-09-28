<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pitch this campaign makes is "we already built you one", so the built thing has
 * to live on the prospect: where it is, what it looks like, and what they said about it.
 *
 * The token is what makes the landing page addressable without exposing the row id, and
 * it is generated per prospect rather than signed like the unsubscribe URL because this
 * link is meant to be forwarded around an office — a signature tied to an expiring
 * request would break the moment somebody passes it to their partner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            // Where their tailored design is deployed, e.g. divstrong.com/embroideryfactory.
            $table->string('preview_url')->nullable()->after('website');

            // Public id for /p/{token}. Unique so a lookup can never be ambiguous.
            $table->string('preview_token', 32)->nullable()->unique()->after('preview_url');

            // Optional screenshot used as the hero of the campaign email.
            $table->string('preview_image')->nullable()->after('preview_token');

            $table->timestamp('preview_viewed_at')->nullable()->after('preview_image');

            // Their two answers. Null means not asked yet, which is different from "no"
            // and has to stay tellable apart for the follow-up logic.
            $table->boolean('likes_design')->nullable()->after('preview_viewed_at');
            $table->boolean('interested')->nullable()->after('likes_design');
            $table->timestamp('responded_at')->nullable()->after('interested');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'preview_url', 'preview_token', 'preview_image', 'preview_viewed_at',
                'likes_design', 'interested', 'responded_at',
            ]);
        });
    }
};
