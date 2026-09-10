<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backing tables for "Get Some!" — automated prospect discovery.
 *
 * Two tables rather than one because a run and its candidates have different lifetimes and
 * different jobs. The run row is the progress bar: the page polls it while work is in flight.
 * The candidate rows are the audit trail, and they outlive the run — when 50 leads go in and 12
 * come out, the only useful question is "what happened to the other 38", and that answer has to
 * survive somewhere queryable rather than in a notification that disappears on the next click.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_discovery_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            // Who the imported prospects land with. Separate from requested_by: an admin can
            // run discovery on behalf of a rep.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('source')->nullable();
            $table->unsignedSmallInteger('target_count')->default(50);

            // Free-text niche/region steering, plus whatever else the modal grows later.
            $table->json('criteria')->nullable();

            $table->string('status', 20)->default('pending');
            $table->string('stage_message')->nullable();

            // Discovery happens in rounds so no single HTTP request runs long. This is how the
            // runner knows where it left off between polls.
            $table->unsignedSmallInteger('round')->default(0);

            $table->unsignedInteger('companies_found')->default(0);
            $table->unsignedInteger('emails_harvested')->default(0);
            $table->unsignedInteger('accepted')->default(0);
            $table->unsignedInteger('rejected')->default(0);
            $table->unsignedInteger('imported')->default(0);

            // Search cost, banked per run. Nobody can eyeball this: web search
            // re-bills its whole accumulated context on every step of its loop, so
            // spend scales with the square of the search budget rather than with
            // companies found. Zero on the Places path, which costs no tokens at all.
            $table->unsignedBigInteger('tokens_in')->default(0);
            $table->unsignedBigInteger('tokens_out')->default(0);
            $table->unsignedInteger('searches')->default(0);

            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // The list page looks up "is anything running right now" on every poll.
            $table->index(['status', 'created_at']);
        });

        Schema::create('prospect_discovery_candidates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('run_id')->constrained('prospect_discovery_runs')->cascadeOnDelete();

            $table->string('company')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 64)->nullable();

            // The page the address was actually read off. Every accepted email has one, because
            // an email without a page we fetched ourselves is an email nobody can vouch for.
            $table->text('source_url')->nullable();

            $table->string('status', 20)->default('pending');
            $table->string('reject_reason')->nullable();

            // Per-gate results, kept whole so a rejection can be re-read later without
            // re-running the checks against someone else's mail server.
            $table->json('checks')->nullable();

            $table->foreignId('prospect_id')->nullable()->constrained('prospects')->nullOnDelete();

            $table->timestamps();

            $table->index(['run_id', 'status']);
            // Harvesting checks "have we already seen this address in this run" constantly.
            $table->index(['run_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_discovery_candidates');
        Schema::dropIfExists('prospect_discovery_runs');
    }
};
