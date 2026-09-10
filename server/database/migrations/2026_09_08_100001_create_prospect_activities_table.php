<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Everything that has ever happened to a prospect, in one append-only timeline.
 *
 * Sends are written here by ProspectMailer; opens, clicks and bounces arrive later
 * from the Postmark webhook and are stitched onto the same row. That is what makes
 * the engagement chips on the list possible without a column per email per state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prospect_id')->constrained('prospects')->cascadeOnDelete();

            $table->string('type', 32);

            // Who did it, when a person did. Webhook events have no user.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('description')->nullable();

            // Per-type payload: the email label, the clicked URL, the bounce reason.
            $table->json('meta')->nullable();

            // Postmark MessageID where we have one. A secondary correlation key —
            // over SMTP this holds an MTA queue id that never matches the webhook's
            // GUID, which is why metadata is the primary path.
            $table->string('external_id')->nullable();

            // Not created_at: a webhook can arrive minutes after the event it
            // reports, and the timeline has to read in the order things happened.
            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['prospect_id', 'type']);
            $table->index(['type', 'occurred_at']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_activities');
    }
};
