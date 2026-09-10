<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Mail\ProspectSalesMail;
use App\Mail\TemplatePreview;
use App\Models\EmailTemplate;
use App\Support\Outreach;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->sendPreviewAction(),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * Sends the template to a real inbox.
     *
     * The editor already has a live preview beside it, so this is not for checking layout.
     * What a preview pane can never answer is how the email survives an actual mail client:
     * Outlook's HTML quirks, a dark background inverted by a client that decides to, images
     * blocked by default, how the subject truncates on a phone. That needs a real send.
     *
     * Sends the unsaved editor state, so a draft can be tested before committing it.
     */
    protected function sendPreviewAction(): Actions\Action
    {
        return Actions\Action::make('sendPreview')
            ->label('Send Preview')
            ->color('gray')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Send a test of this template')
            ->modalDescription('Sends what is currently in the editor, including unsaved changes. The recipient name replaces the placeholders, so you can check the personalisation reads correctly.')
            ->modalSubmitActionLabel('Send test')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Recipient Name')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => Auth::user()?->name)
                    ->helperText('Stands in for the prospect name wherever the template uses it.'),

                Forms\Components\TextInput::make('email')
                    ->label('Send To')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => Auth::user()?->email),
            ])
            ->action(function (array $data) {
                $state = $this->form->getRawState();
                $vars = $this->sampleVars($data['name']);

                $subject = strip_tags(EmailTemplate::interpolate((string) ($state['subject'] ?? ''), $vars));
                $body = $this->fillSlots(
                    EmailTemplate::interpolate((string) ($state['body'] ?? ''), $vars),
                    $vars,
                );

                $html = view('emails.outreach.shell', [
                    'bodyHtml' => $body,
                    'subject' => $subject,
                    'brandUrl' => ProspectSalesMail::BRAND_URL,
                    // The compliance block is shown in the test too. If the postal address
                    // is missing, the person editing outreach copy is exactly who should
                    // find that out.
                    'postalAddress' => Outreach::postalAddress(),
                    'unsubscribeUrl' => '#',
                ])->render();

                try {
                    Mail::to($data['email'], $data['name'])->send(
                        new TemplatePreview($subject, $html, Auth::user()?->email),
                    );
                } catch (\Throwable $e) {
                    Log::error('Email template preview send failed', [
                        'template' => $this->record?->key,
                        'error' => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Test Not Sent')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Test Sent')
                    ->body('Sent to '.$data['email'].'. Subject is prefixed [TEST].')
                    ->send();
            });
    }

    /**
     * Fill the slots exactly as a real send would.
     *
     * Delegates to ProspectSalesMail rather than repeating the markup: when this page kept its
     * own copy, a test send could show a greeting and a button that differed from what the
     * recipient actually got — which defeats the point of testing it in a real client.
     *
     * @param  array<string, string>  $vars
     */
    protected function fillSlots(string $body, array $vars): string
    {
        return ProspectSalesMail::fillSlotsFor(
            $body,
            greeting: ProspectSalesMail::greetingHtmlFor($vars['prospect_name'], $vars['notes'] ?? null),
            cta: ProspectSalesMail::ctaHtmlFor($this->ctaLabelForTemplate()),
            signature: ProspectSalesMail::signatureHtmlFor($vars['sender_name']),
        );
    }

    /**
     * The button wording this template's mailable would use. General Update softens it; the
     * two intros ask for the meeting outright.
     */
    protected function ctaLabelForTemplate(): string
    {
        return $this->record?->key === EmailTemplate::GENERAL_UPDATE
            ? 'Get in touch'
            : 'Book 15 minutes';
    }

    /**
     * Representative values for every placeholder any template uses, so a test send never
     * shows a raw {{ token }}.
     *
     * @return array<string, string>
     */
    protected function sampleVars(?string $name = null): array
    {
        return [
            'prospect_name' => $name ?: 'Dana Ruiz',
            'company' => 'Northbound Creative',
            'notes' => 'Following up on our call about the client portal.',
            'sender_name' => Auth::user()?->name ?: ProspectSalesMail::FALLBACK_SENDER_NAME,
            'sender_email' => Auth::user()?->email ?: ProspectSalesMail::FALLBACK_SENDER_EMAIL,
            'schedule_url' => config('prospecting.outreach.schedule_url'),
        ];
    }
}
