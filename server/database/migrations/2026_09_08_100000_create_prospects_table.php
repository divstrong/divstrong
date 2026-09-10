<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The outreach book: creative and media teams divStrong could augment.
 *
 * Separate from `clients` on purpose. A client is somebody we have a relationship
 * and a paper trail with; a prospect is a stranger with a published email address
 * and a plausible reason to want us. Merging the two would put cold leads into
 * every client dropdown in the panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();

            // Nullable: discovery can find an agency and its address before it
            // finds a human name, and an unnamed row is still worth keeping while
            // NameFinder has another go at it.
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('title')->nullable();
            $table->string('website')->nullable();

            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 64)->nullable();
            $table->string('zip', 32)->nullable();

            // Which of the three sales stories fits them. Drives the default email
            // action on the row, nothing more — it is a hint, not a lock.
            $table->string('segment', 20)->default('agency');

            $table->string('status', 20)->default('new');
            $table->string('lead_status', 20)->default('unqualified');
            $table->timestamp('called_at')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->text('notes')->nullable();

            // Segment tag for a batch — "Discovery Sep 8", "AIGA list", etc.
            $table->string('source')->nullable();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Opt-out state. Checked by every path that sends commercial mail.
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_source', 20)->nullable();

            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // The list filters on these constantly, and discovery checks
            // "already in the book" once per candidate.
            $table->index('email');
            $table->index(['lead_status', 'created_at']);
            $table->index('segment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
