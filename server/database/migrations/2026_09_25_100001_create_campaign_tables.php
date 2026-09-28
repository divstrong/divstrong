<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drip campaigns: a named sequence of emails, and a row per prospect walking through it.
 *
 * Three tables rather than a column on `prospects` because the thing being tracked is a
 * position in a sequence over time, and because the same prospect may be worked by a
 * second campaign later. The enrollment is the unit of work the scheduler reads: one
 * indexed query for "who is due" regardless of how many campaigns exist.
 *
 * Copy is NOT stored here. Steps point at an email_templates key, so the words stay
 * editable in the panel and a template that has been rewritten does not need the
 * campaign rebuilt around it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Guards that apply to every step, kept with the campaign so a paused
            // campaign stops sending without touching each enrollment.
            $table->boolean('stop_on_reply')->default(true);
            $table->boolean('stop_on_booking')->default(true);

            $table->timestamps();
        });

        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(1);
            $table->string('name');

            // The email_templates key this step renders.
            $table->string('template_key');

            /*
             * Days after the PREVIOUS step went out — or after enrolment, for the first
             * step. Relative rather than absolute so inserting a step in the middle does
             * not require rewriting the ones after it.
             */
            $table->unsignedSmallInteger('delay_days')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'position']);
        });

        Schema::create('campaign_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();

            // active | completed | stopped
            $table->string('status', 20)->default('active');

            // The last step actually sent; null until the first one goes out.
            $table->unsignedSmallInteger('last_step_position')->nullable();
            $table->timestamp('last_sent_at')->nullable();

            // When the next step is due. The scheduler's whole query is this column,
            // so it is indexed with status.
            $table->timestamp('next_send_at')->nullable();

            $table->string('stop_reason', 40)->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One live walk through a campaign per prospect.
            $table->unique(['campaign_id', 'prospect_id']);
            $table->index(['status', 'next_send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_enrollments');
        Schema::dropIfExists('campaign_steps');
        Schema::dropIfExists('campaigns');
    }
};
