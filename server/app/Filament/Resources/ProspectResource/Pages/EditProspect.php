<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use App\Models\Client;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProspect extends EditRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('success')
                // Filament picks dark text on green for contrast; force white on a deeper green.
                ->extraAttributes(['style' => '--bg: var(--success-600); --hover-bg: var(--success-700); --dark-bg: var(--success-600); --dark-hover-bg: var(--success-700); --text: #fff; --hover-text: #fff; --dark-text: #fff; --dark-hover-text: #fff;'])
                ->url(fn () => $this->getRecord()?->preview_url, shouldOpenInNewTab: true)
                ->visible(fn () => filled($this->getRecord()?->preview_url)),

            // The landing page the drip emails link to, with the feedback questions.
            Actions\Action::make('conversion')
                ->label('Conversion')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->url(fn () => $this->getRecord()?->previewLandingUrl(), shouldOpenInNewTab: true)
                ->visible(fn () => filled($this->getRecord()?->preview_url)),

            // The same three emails as the list row, but nothing hidden: deliberately
            // re-sending an intro to a corrected address is a thing people do, and this is
            // the only place to do it. See ProspectResource::emailActions().
            Actions\ActionGroup::make([
                ...ProspectResource::emailActions(hideSent: false),
                ...ProspectResource::campaignStepActions(),
            ])
                ->label('Send')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->button()
                ->hidden(fn () => $this->getRecord()?->isUnsubscribed() ?? false),

            $this->resubscribeAction(),

            $this->convertToClientAction(),

            Actions\DeleteAction::make()
                ->color('gray')
                ->icon('heroicon-o-trash'),
        ];
    }

    /**
     * Put someone back on the list.
     *
     * Only ever by hand, and only visible on someone who actually opted out — an opt-out is
     * reversed because the person told a rep to start again, never because a process
     * decided to. The confirmation says so in as many words, because the legal exposure
     * here belongs to whoever clicks it.
     */
    protected function resubscribeAction(): Actions\Action
    {
        return Actions\Action::make('resubscribe')
            ->label('Re-subscribe')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn () => $this->getRecord()?->isUnsubscribed() ?? false)
            ->requiresConfirmation()
            ->modalHeading('Re-enable outreach to this prospect')
            ->modalDescription(fn () => 'They opted out '
                .($this->getRecord()?->unsubscribed_at?->diffForHumans() ?? 'previously')
                .($this->getRecord()?->unsubscribe_source === Prospect::UNSUB_COMPLAINT
                    ? ' by marking our email as spam. Mailing them again risks the sending domain.'
                    : '.')
                .' Only do this if they have asked you to start emailing them again.')
            ->modalSubmitActionLabel('Re-subscribe')
            ->action(function () {
                $this->getRecord()->resubscribe();

                Notification::make()
                    ->success()
                    ->title('Outreach re-enabled')
                    ->send();
            });
    }

    /**
     * Promote a prospect into the clients table.
     *
     * Copies rather than moves: the prospect row stays, linked, because its timeline is the
     * record of how the relationship started and deleting it would take the history of
     * every email with it. `status` goes to won so the pipeline agrees with reality.
     */
    protected function convertToClientAction(): Actions\Action
    {
        return Actions\Action::make('convertToClient')
            ->label('Convert to Client')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('gray')
            ->visible(fn () => ! ($this->getRecord()?->isConverted() ?? true))
            ->requiresConfirmation()
            ->modalHeading('Convert Prospect to Client')
            ->modalDescription('Creates a client record from this prospect. The prospect stays here, linked, so its email history is not lost.')
            ->modalSubmitActionLabel('Convert')
            ->action(function () {
                $record = $this->getRecord();

                // Clients require an email; a prospect does not. Refuse rather than write a
                // half client somebody has to find and fix later.
                if (blank($record->email)) {
                    Notification::make()
                        ->danger()
                        ->title('No email address')
                        ->body('Add an email to this prospect before converting — a client record needs one.')
                        ->send();

                    return;
                }

                $client = Client::create([
                    'name' => $record->name ?: ($record->company ?: 'Unnamed'),
                    'title' => $record->title,
                    'email' => $record->email,
                    'phone' => $record->phone,
                    'company' => $record->company,
                    // Client stores a bare domain and renders it behind https://.
                    'domain' => $record->website
                        ? preg_replace('~^https?://~i', '', rtrim($record->website, '/'))
                        : null,
                    'address1' => $record->address1,
                    'address2' => $record->address2,
                    'city' => $record->city,
                    'state' => $record->state,
                    'zip' => $record->zip,
                    'notes' => $record->notes,
                ]);

                $record->update([
                    'client_id' => $client->id,
                    'converted_at' => now(),
                    'converted_by' => auth()->id(),
                    'status' => 'won',
                    'lead_status' => Prospect::LEAD_QUALIFIED,
                ]);

                ProspectActivity::record([
                    'prospect_id' => $record->id,
                    'type' => ProspectActivity::CONVERTED,
                    'user_id' => auth()->id(),
                    'description' => 'Converted to client: '.$client->name,
                    'meta' => ['client_id' => $client->id],
                ]);

                Notification::make()
                    ->success()
                    ->title('Prospect Converted')
                    ->body($client->name.' is now a client.')
                    ->send();

                return redirect(\App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $client->id]));
            });
    }
}
