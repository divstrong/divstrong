<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A milestone can be billed with a real PayPal invoice — something the client can file and
 * reconcile against — rather than only through the on-page payment form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposal_milestones', function (Blueprint $table) {
            $table->string('invoice_paypal_id')->nullable()->after('paid_at');
            $table->string('invoice_number')->nullable()->after('invoice_paypal_id');
            $table->string('invoice_url')->nullable()->after('invoice_number');
            $table->json('invoice_recipients')->nullable()->after('invoice_url');
            $table->timestamp('invoice_sent_at')->nullable()->after('invoice_recipients');
            $table->unsignedSmallInteger('invoice_send_count')->default(0)->after('invoice_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_milestones', function (Blueprint $table) {
            $table->dropColumn(['invoice_paypal_id', 'invoice_number', 'invoice_url', 'invoice_recipients', 'invoice_sent_at', 'invoice_send_count']);
        });
    }
};
