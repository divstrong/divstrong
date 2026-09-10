<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable copy for the outreach emails.
 *
 * A row here overrides the Blade fallback its mailable ships with, so the sales
 * wording can be rewritten from the panel without a deploy. Deactivate the row and
 * the built-in version takes over again — which is also the recovery path when an
 * edit goes wrong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();

            // The machine key the mailables look up. Unique, and locked in the UI
            // once set: renaming it silently detaches the template.
            $table->string('key')->unique();

            $table->string('name');
            $table->string('subject');
            $table->longText('body');

            // What placeholders this template understands, shown above the editor.
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
