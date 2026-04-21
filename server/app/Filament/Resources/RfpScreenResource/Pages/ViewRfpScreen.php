<?php

namespace App\Filament\Resources\RfpScreenResource\Pages;

use App\Filament\Resources\RfpScreenResource;
use App\Services\ClaudeService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use App\Models\RfpScreenAttachment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ViewRfpScreen extends ViewRecord
{
    protected static string $resource = RfpScreenResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('rfp_name')
                            ->label('RFP Name')
                            ->placeholder('Untitled RFP')
                            ->weight('bold')
                            ->size('2xl')
                            ->columnSpan(8)
                            ->hintAction(
                                Action::make('editName')
                                    ->icon('heroicon-o-pencil-square')
                                    ->color('gray')
                                    ->tooltip('Edit RFP Name')
                                    ->form([
                                        Forms\Components\TextInput::make('rfp_name')
                                            ->label('RFP Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->default(fn ($record) => $record->rfp_name),
                                    ])
                                    ->action(function (array $data, $record) {
                                        $record->update(['rfp_name' => $data['rfp_name']]);
                                        Notification::make()
                                            ->title('RFP name updated')
                                            ->success()
                                            ->send();
                                    })
                            ),
                        ViewEntry::make('score')
                            ->view('filament.rfp-screen-score')
                            ->columnSpan(4),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->placeholder('—')
                            ->weight('bold')
                            ->icon('heroicon-o-calendar-days')
                            ->formatStateUsing(function ($record) {
                                if (! $record->due_date) return '—';
                                $days = (int) now()->startOfDay()->diffInDays($record->due_date, false);
                                $formatted = $record->due_date->format('M j, Y');
                                if ($days < 0) return $formatted . ' · ' . abs($days) . ' days overdue';
                                if ($days === 0) return $formatted . ' · Due today';
                                return $formatted . ' · in ' . $days . ' days';
                            })
                            ->color(function ($record) {
                                if (! $record->due_date) return 'gray';
                                $days = now()->startOfDay()->diffInDays($record->due_date, false);
                                if ($days < 0) return 'danger';
                                if ($days <= 7) return 'warning';
                                return 'primary';
                            })
                            ->columnSpan(4),
                        TextEntry::make('pre_bid_conference_date')
                            ->label('Pre-Bid Conference')
                            ->placeholder('Not listed')
                            ->weight('bold')
                            ->icon('heroicon-o-users')
                            ->formatStateUsing(function ($record) {
                                $parts = [];
                                if ($record->pre_bid_conference_date) {
                                    $parts[] = $record->pre_bid_conference_date->format('M j, Y');
                                }
                                if ($record->pre_bid_conference_details) {
                                    $parts[] = $record->pre_bid_conference_details;
                                }
                                return empty($parts) ? 'Not listed' : implode(' · ', $parts);
                            })
                            ->color(fn ($record) => $record->pre_bid_conference_date || $record->pre_bid_conference_details ? 'primary' : 'gray')
                            ->columnSpan(4),
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'completed' => 'success',
                                'analyzing' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => ucfirst($state))
                            ->columnSpan(4),
                        TextEntry::make('file_path')
                            ->label('Original RFP')
                            ->formatStateUsing(fn ($record) => $record->original_filename)
                            ->url(fn ($record) => Storage::disk('public')->url($record->file_path))
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('primary')
                            ->columnSpan(8),
                    ]),

                Section::make('Contact Person')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('contact_name')
                            ->label('Name')
                            ->placeholder('—')
                            ->weight('semibold'),
                        TextEntry::make('contact_title')
                            ->label('Title')
                            ->placeholder('—'),
                        TextEntry::make('contact_department')
                            ->label('Department')
                            ->placeholder('—'),
                        TextEntry::make('contact_email')
                            ->label('Email')
                            ->placeholder('—')
                            ->icon('heroicon-o-envelope')
                            ->url(fn ($record) => $record->contact_email ? 'mailto:' . $record->contact_email : null)
                            ->color(fn ($record) => $record->contact_email ? 'primary' : null),
                        TextEntry::make('contact_phone')
                            ->label('Phone')
                            ->placeholder('—')
                            ->icon('heroicon-o-phone')
                            ->url(fn ($record) => $record->contact_phone ? 'tel:' . preg_replace('/[^0-9+]/', '', $record->contact_phone) : null)
                            ->color(fn ($record) => $record->contact_phone ? 'primary' : null),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => filled($record->contact_name) || filled($record->contact_email) || filled($record->contact_phone) || filled($record->contact_title) || filled($record->contact_department)),

                Section::make('Summary')
                    ->schema([
                        TextEntry::make('summary')
                            ->hiddenLabel()
                            ->markdown(),
                    ])
                    ->collapsible(),

                Section::make('Red Flags')
                    ->schema([
                        TextEntry::make('red_flags')
                            ->hiddenLabel()
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No red flags detected.'),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record->red_flags)),

                Section::make('Key Requirements')
                    ->schema([
                        TextEntry::make('requirements')
                            ->hiddenLabel()
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No specific requirements extracted.'),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record->requirements)),

                Section::make('Submission Requirements')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->description('How the proposal must be prepared, packaged, and submitted.')
                    ->schema([
                        TextEntry::make('submission_requirements')
                            ->hiddenLabel()
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No specific submission requirements extracted.'),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record->submission_requirements)),

                Section::make('Notes')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Internal-only notes and supporting context for this RFP screen.')
                    ->schema([
                        Livewire::make('rfp-screen-notes', fn ($livewire) => [
                            'rfpScreenId' => $livewire->getRecord()?->id ?? 0,
                        ]),
                    ])
                    ->collapsible(),

                Section::make('Supporting Documents')
                    ->description('Additional documents uploaded after the initial screen. These are considered in re-analyses.')
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('original_filename')
                                    ->hiddenLabel()
                                    ->url(fn (RfpScreenAttachment $record) => Storage::disk('public')->url($record->file_path))
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-document')
                                    ->color('primary'),
                                TextEntry::make('created_at')
                                    ->label('Uploaded')
                                    ->dateTime('M j, Y g:i A'),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->attachments()->exists()),

                Section::make('Document Details')
                    ->schema([
                        TextEntry::make('original_filename')
                            ->label('File'),
                        TextEntry::make('file_type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => strtoupper($state)),
                        TextEntry::make('user.name')
                            ->label('Submitted By'),
                        TextEntry::make('created_at')
                            ->label('Submitted At')
                            ->dateTime('M j, Y g:i A'),
                        TextEntry::make('analyzed_at')
                            ->label('Analyzed At')
                            ->dateTime('M j, Y g:i A')
                            ->placeholder('Not yet analyzed'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Analysis Prompt Used')
                    ->schema([
                        TextEntry::make('prompt')
                            ->label('')
                            ->markdown(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Raw AI Response')
                    ->schema([
                        TextEntry::make('raw_response')
                            ->label('')
                            ->markdown(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->raw_response)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addAttachments')
                ->label('Supporting Docs')
                ->icon('heroicon-o-paper-clip')
                ->color('gray')
                ->form([
                    Forms\Components\FileUpload::make('files')
                        ->label('Supporting Documents')
                        ->multiple()
                        ->directory('rfp-documents')
                        ->disk('public')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'text/plain',
                            'text/csv',
                            'text/markdown',
                        ])
                        ->maxSize(20480)
                        ->required()
                        ->helperText('PDF, DOC, DOCX, TXT, CSV, MD (max 20MB each). The RFP will be re-analyzed against the full document set.'),
                ])
                ->modalSubmitActionLabel('Upload & Rescan')
                ->action(function (array $data) {
                    $record = $this->record;
                    $files = (array) ($data['files'] ?? []);

                    foreach ($files as $filePath) {
                        $record->attachments()->create([
                            'filename' => basename($filePath),
                            'original_filename' => basename($filePath),
                            'file_path' => $filePath,
                            'file_type' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
                        ]);
                    }

                    $this->runReanalysis();
                }),
            Actions\Action::make('reanalyze')
                ->label('Rescan')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Rescan this RFP?')
                ->modalDescription('Optionally upload a new version of the RFP to replace the existing primary document. Leave blank to re-analyze the existing file. Supporting documents will still be included.')
                ->modalSubmitActionLabel('Rescan')
                ->form([
                    Forms\Components\FileUpload::make('replacement_file')
                        ->label('Replace Primary RFP (optional)')
                        ->directory('rfp-documents')
                        ->disk('public')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'text/plain',
                            'text/csv',
                            'text/markdown',
                        ])
                        ->maxSize(20480)
                        ->helperText('PDF, DOC, DOCX, TXT, CSV, MD (max 20MB). If provided, the existing primary RFP file will be replaced.'),
                ])
                ->action(function (array $data) {
                    $replacement = $data['replacement_file'] ?? null;

                    if (! empty($replacement)) {
                        $record = $this->record;
                        $oldPath = $record->file_path;

                        $record->update([
                            'file_path' => $replacement,
                            'filename' => basename($replacement),
                            'original_filename' => basename($replacement),
                            'file_type' => strtolower(pathinfo($replacement, PATHINFO_EXTENSION)),
                        ]);

                        if ($oldPath && $oldPath !== $replacement) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }

                    $this->runReanalysis();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function runReanalysis(): void
    {
        $record = $this->record;
        $record->update(['status' => 'analyzing']);

        try {
            $service = new ClaudeService();
            $prompt = $record->prompt;
            if (! str_contains((string) $prompt, 'contact_name')) {
                $prompt = RfpScreenResource::getDefaultPrompt();
            }

            $result = $service->analyzeRfp(
                $record->file_path,
                $prompt,
                $record->attachments()->pluck('file_path')->all(),
            );

            $updateData = [
                'score' => $result['score'],
                'due_date' => $result['due_date'] ?? $record->due_date,
                'pre_bid_conference_date' => $result['pre_bid_conference_date'] ?? $record->pre_bid_conference_date,
                'pre_bid_conference_details' => $result['pre_bid_conference_details'] ?? $record->pre_bid_conference_details,
                'contact_name' => $result['contact_name'] ?? $record->contact_name,
                'contact_title' => $result['contact_title'] ?? $record->contact_title,
                'contact_department' => $result['contact_department'] ?? $record->contact_department,
                'contact_email' => $result['contact_email'] ?? $record->contact_email,
                'contact_phone' => $result['contact_phone'] ?? $record->contact_phone,
                'summary' => $result['summary'],
                'red_flags' => $result['red_flags'],
                'requirements' => $result['requirements'],
                'submission_requirements' => $result['submission_requirements'] ?? [],
                'raw_response' => $result['raw_response'],
                'status' => 'completed',
                'analyzed_at' => now(),
            ];

            if (empty($record->rfp_name) && !empty($result['rfp_name'])) {
                $updateData['rfp_name'] = $result['rfp_name'];
            }

            $record->update($updateData);

            Notification::make()
                ->title("Rescan Complete — {$record->score}/100")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('RFP re-screening failed', [
                'rfp_screen_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            $record->update([
                'status' => 'failed',
                'raw_response' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Rescan Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->refreshFormData(['rfp_name', 'contact_name', 'contact_title', 'contact_department', 'contact_email', 'contact_phone', 'due_date', 'pre_bid_conference_date', 'pre_bid_conference_details', 'score', 'summary', 'red_flags', 'requirements', 'submission_requirements', 'raw_response', 'status', 'analyzed_at', 'file_path', 'original_filename', 'filename', 'file_type']);
    }
}
