<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hosting accounts and the renewal invoices raised against them.
 *
 * The term amount is stored rather than derived from the monthly rate: most accounts bill
 * rate × 12, but not all of them do, and the invoice has to say what was agreed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('domain');
            $table->string('billing_email')->nullable();
            $table->date('term_start');
            $table->date('term_end');
            $table->decimal('monthly_rate', 10, 2)->default(0);
            $table->decimal('term_amount', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->boolean('auto_renew')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'term_end']);
        });

        Schema::create('hosting_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->date('term_start');
            $table->date('term_end');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->string('paypal_invoice_id')->nullable()->unique();
            $table->string('invoice_number')->nullable();
            $table->string('payment_url')->nullable();
            $table->json('recipients')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->json('paypal_response')->nullable();
            $table->timestamps();

            $table->unique(['hosting_account_id', 'term_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_invoices');
        Schema::dropIfExists('hosting_accounts');
    }
};
