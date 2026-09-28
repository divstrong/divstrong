<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calls booked from a preview page or the public booking page.
 *
 * Times are stored in UTC — the prospect's timezone is kept alongside purely so the
 * confirmation mail and the panel can say the time back in the words they chose it in.
 * Comparing availability in anything but UTC is how a booking ends up an hour out twice
 * a year.
 *
 * A cancelled row is kept, not deleted: it is the record that the slot was once taken,
 * and a prospect who books and cancels is a different signal from one who never booked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            // Null for a booking made from the generic /book page by someone who is not
            // on the prospect list yet.
            $table->foreignId('prospect_id')->nullable()->constrained()->nullOnDelete();

            // Public id for the confirmation and cancel links.
            $table->string('token', 32)->unique();

            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 64)->default('America/New_York');

            // booked | canceled
            $table->string('status', 20)->default('booked');
            $table->timestamp('canceled_at')->nullable();
            $table->string('canceled_by', 20)->nullable();

            // Where the booking came from: preview page, public page, admin.
            $table->string('source', 20)->default('preview');

            $table->timestamps();

            // The availability query asks "what is taken in this range", every time.
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
