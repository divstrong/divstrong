<?php

namespace App\Console\Commands;

use App\Mail\ProspectSalesMail;
use App\Models\EmailTemplate;
use Illuminate\Console\Command;

/**
 * Loads the outreach emails into editable `email_templates` rows.
 *
 * The bodies are the same Blade partials the mailables fall back to, rendered once and
 * stored, so the row and the fallback start identical and Marketing can edit from a real
 * starting point rather than an empty box.
 *
 * Idempotent and non-destructive by default: an existing row is left exactly as it is,
 * because the whole point of the row is that somebody rewrote it. --force overwrites,
 * which is the "put it back how it shipped" button.
 */
class SeedEmailTemplates extends Command
{
    protected $signature = 'email:seed-templates
        {--force : Overwrite templates that already exist}
        {--only=* : Limit to these template keys, e.g. --only=promo_preview_intro}';

    protected $description = 'Create or refresh the editable outreach email templates';

    /**
     * Key => [name, subject, blade view, what placeholders it understands].
     *
     * The subjects here must match each mailable's defaultSubject(), or deactivating a
     * template would silently change the subject line as well as the body.
     */
    protected function definitions(): array
    {
        return [
            EmailTemplate::AGENCY_INTRO => [
                'name' => 'Agency Intro',
                'subject' => 'A dev bench for your agency, without the headcount',
                'view' => 'emails.outreach.partials.agency-intro-body',
                'description' => 'White-label pitch for agencies and studios. The greeting, '
                    .'your personal note and the sign-off are added automatically — do not '
                    .'write your own. Placeholders: {{ prospect_name }}, {{ company }}, '
                    .'{{ sender_name }}, {{ schedule_url }}.',
            ],
            EmailTemplate::CLIENT_INTRO => [
                'name' => 'Client Intro',
                'subject' => 'Custom software in days, not weeks',
                'view' => 'emails.outreach.partials.client-intro-body',
                'description' => 'Direct pitch for companies buying delivery themselves. The '
                    .'greeting, your personal note and the sign-off are added automatically. '
                    .'Placeholders: {{ prospect_name }}, {{ company }}, {{ sender_name }}, '
                    .'{{ schedule_url }}.',
            ],
            /*
             * The promotional-products preview campaign. These four are a sequence rather
             * than three alternatives, so the descriptions say where each one sits — the
             * copy only makes sense in order.
             *
             * The greeting, the sign-off, the preview card and its button are all added by
             * the shell. Write prose here and nothing else.
             */
            'promo_preview_intro' => [
                'name' => 'Preview · 1. The concept',
                'subject' => 'A website concept for {{ company }}',
                'view' => 'emails.campaign.steps.preview-intro',
                'description' => 'Step 1, sent on enrolment. Deliberately carries no price — '
                    .'the first cold email asks for curiosity, not a purchase decision. '
                    .'Placeholders: {{ company }}, {{ first_name }}, {{ preview_url }}, {{ sender_name }}.',
            ],
            'promo_preview_nudge' => [
                'name' => 'Preview · 2. Nudge',
                'subject' => 'Did the concept for {{ company }} land?',
                'view' => 'emails.campaign.steps.preview-nudge',
                'description' => 'Step 2, three days later. Short by design — a reminder, not '
                    .'a second pitch. Placeholders: {{ company }}, {{ first_name }}.',
            ],
            'promo_preview_details' => [
                'name' => 'Preview · 3. What launching looks like',
                'subject' => 'What it takes to put {{ company }} live',
                'view' => 'emails.campaign.steps.preview-details',
                'description' => 'Step 3, a week in, and the first email that mentions money. '
                    .'Move or delete that paragraph freely. Placeholders: {{ company }}, {{ first_name }}.',
            ],
            'promo_preview_last' => [
                'name' => 'Preview · 4. Last note',
                'subject' => 'Last note on the concept for {{ company }}',
                'view' => 'emails.campaign.steps.preview-last',
                'description' => 'Step 4, two weeks in. A real close with an easy out — which '
                    .'is what earns the occasional late reply. Placeholders: {{ company }}.',
            ],

            EmailTemplate::GENERAL_UPDATE => [
                'name' => 'General Update',
                'subject' => 'What we have been building at divStrong',
                'view' => 'emails.outreach.partials.general-update-body',
                'description' => 'The open one — news, launches, events, offers. Expect to '
                    .'rewrite the body and the subject before each send. Placeholders: '
                    .'{{ prospect_name }}, {{ company }}, {{ sender_name }}, {{ schedule_url }}.',
            ],
        ];
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $only = (array) $this->option('only');

        foreach ($this->definitions() as $key => $definition) {
            if ($only !== [] && ! in_array($key, $only, true)) {
                continue;
            }

            $existing = EmailTemplate::where('key', $key)->first();

            if ($existing && ! $force) {
                $this->line("  skipped  {$key} (already exists — use --force to overwrite)");

                continue;
            }

            // Rendered with the same variables the mailable passes, so a placeholder the
            // Blade fallback uses — schedule_url, in every one of these — comes out as a
            // real URL in the stored body rather than blowing up on an undefined variable.
            $body = view($definition['view'], [
                'prospect_name' => 'there',
                'company' => '',
                'notes' => null,
                'sender_name' => ProspectSalesMail::FALLBACK_SENDER_NAME,
                'sender_email' => ProspectSalesMail::FALLBACK_SENDER_EMAIL,
                'schedule_url' => config('prospecting.outreach.schedule_url'),
            ])->render();

            EmailTemplate::updateOrCreate(['key' => $key], [
                'name' => $definition['name'],
                'subject' => $definition['subject'],
                'body' => trim($body),
                'description' => $definition['description'],
                'is_active' => true,
            ]);

            $this->line(($existing ? '  updated  ' : '  created  ').$key);
        }

        $this->info('Outreach email templates are up to date.');

        return self::SUCCESS;
    }
}
