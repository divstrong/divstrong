<?php

namespace App\Filament\Resources\RfpScreenResource\Pages;

use App\Enums\ProposalStatus;
use App\Filament\Resources\ProposalResource;
use App\Filament\Resources\RfpScreenResource;
use App\Models\Proposal;
use App\Models\TermsLibrary;
use App\Services\ClaudeService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use App\Models\RfpScreenAttachment;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Group;
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
                        ViewEntry::make('score')
                            ->view('filament.rfp-screen-score')
                            ->columnSpan(2),
                        Group::make()
                            ->columnSpan(8)
                            ->schema([
                                TextEntry::make('rfp_name')
                                    ->hiddenLabel()
                                    ->placeholder('Untitled RFP')
                                    ->html()
                                    ->formatStateUsing(fn ($state) => '<span style="font-size: 1.875rem; font-weight: 800; line-height: 1.15; letter-spacing: -0.02em; display: block;">' . e($state ?: 'Untitled RFP') . '</span>'),
                                TextEntry::make('due_date')
                                    ->hiddenLabel()
                                    ->html()
                                    ->formatStateUsing(fn ($record) => '<strong>DUE:</strong> ' . ($record->due_date ? e($record->due_date->format('M j, Y')) : '—')),
                            ]),
                        SchemaActions::make([
                            Action::make('editDetails')
                                ->label('Edit')
                                ->icon('heroicon-o-pencil-square')
                                ->color('gray')
                                ->modalHeading('Edit RFP Details')
                                ->modalWidth('2xl')
                                ->fillForm(fn ($record) => [
                                    'rfp_name' => $record->rfp_name,
                                    'due_date' => $record->due_date?->format('Y-m-d'),
                                    'pre_bid_conference_date' => $record->pre_bid_conference_date?->format('Y-m-d'),
                                    'pre_bid_conference_details' => $record->pre_bid_conference_details,
                                    'contact_name' => $record->contact_name,
                                    'contact_title' => $record->contact_title,
                                    'contact_department' => $record->contact_department,
                                    'contact_email' => $record->contact_email,
                                    'contact_phone' => $record->contact_phone,
                                ])
                                ->form([
                                    Section::make('RFP')
                                        ->columns(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('rfp_name')
                                                ->label('RFP Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                            Forms\Components\DatePicker::make('due_date')
                                                ->label('Due Date')
                                                ->native(false),
                                            Forms\Components\DatePicker::make('pre_bid_conference_date')
                                                ->label('Pre-Bid Conference Date')
                                                ->native(false),
                                            Forms\Components\Textarea::make('pre_bid_conference_details')
                                                ->label('Pre-Bid Conference Details')
                                                ->placeholder('Time, location or meeting link, in-person/virtual, mandatory or optional…')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ]),
                                    Section::make('Contact Person')
                                        ->columns(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('contact_name')
                                                ->label('Name')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('contact_title')
                                                ->label('Title')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('contact_department')
                                                ->label('Department')
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                            Forms\Components\TextInput::make('contact_email')
                                                ->label('Email')
                                                ->email()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('contact_phone')
                                                ->label('Phone')
                                                ->tel()
                                                ->maxLength(50),
                                        ]),
                                ])
                                ->modalSubmitActionLabel('Save Changes')
                                ->action(function (array $data, $record) {
                                    $record->update($data);
                                    Notification::make()
                                        ->title('RFP details updated')
                                        ->success()
                                        ->send();
                                }),
                        ])
                            ->columnSpan(2)
                            ->alignment('end'),
                        TextEntry::make('contact_name')
                            ->hiddenLabel()
                            ->html()
                            ->extraAttributes(['style' => 'margin-top: 2rem;'])
                            ->formatStateUsing(function ($record) {
                                $label = '<div style="font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.06em; color: #000; margin-bottom: 0.5rem;">CONTACT</div>';
                                $lines = [];
                                if (filled($record->contact_name)) {
                                    $name = e($record->contact_name);
                                    if (filled($record->contact_title)) {
                                        $lines[] = $name . ', <em>' . e($record->contact_title) . '</em>';
                                    } else {
                                        $lines[] = $name;
                                    }
                                } elseif (filled($record->contact_title)) {
                                    $lines[] = '<em>' . e($record->contact_title) . '</em>';
                                }
                                if (filled($record->contact_department)) {
                                    $lines[] = e($record->contact_department);
                                }
                                if (filled($record->contact_email)) {
                                    $email = e($record->contact_email);
                                    $lines[] = '<a href="mailto:' . $email . '" class="hover:underline">' . $email . '</a>';
                                }
                                if (filled($record->contact_phone)) {
                                    $cleaned = preg_replace('/[^0-9+]/', '', $record->contact_phone);
                                    $lines[] = '<a href="tel:' . $cleaned . '" class="hover:underline">' . e($record->contact_phone) . '</a>';
                                }
                                $body = empty($lines) ? '—' : implode('<br>', $lines);
                                return $label . '<div>' . $body . '</div>';
                            })
                            ->columnSpan(4)
                            ->columnStart(3),
                        Group::make()
                            ->columnSpan(5)
                            ->extraAttributes(['style' => 'margin-top: 2rem;'])
                            ->schema([
                                TextEntry::make('pre_bid_conference_date')
                                    ->hiddenLabel()
                                    ->html()
                                    ->formatStateUsing(function ($record) {
                                        $label = '<div style="font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.06em; color: #000; margin-bottom: 0.5rem;">PREBID</div>';
                                        $parts = [];
                                        if ($record->pre_bid_conference_date) {
                                            $parts[] = e($record->pre_bid_conference_date->format('M j, Y'));
                                        }
                                        if ($record->pre_bid_conference_details) {
                                            $parts[] = e($record->pre_bid_conference_details);
                                        }
                                        $body = empty($parts) ? 'Not listed' : implode(' · ', $parts);
                                        return $label . '<div>' . $body . '</div>';
                                    }),
                                TextEntry::make('file_path')
                                    ->hiddenLabel()
                                    ->html()
                                    ->formatStateUsing(function ($record) {
                                        $label = '<div style="font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.06em; color: #000; margin-bottom: 0.5rem;">RFP</div>';
                                        $url = e(Storage::disk('public')->url($record->file_path));
                                        $name = e($record->original_filename);
                                        return $label . '<a href="' . $url . '" target="_blank" rel="noopener" class="underline" style="color: #ed2537; white-space: nowrap;">' . $name . '</a>';
                                    }),
                            ]),
                    ]),

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

                Section::make('Budget')
                    ->description(function ($record) {
                        if (! $record->budget_intel_at) return null;
                        $intel = $record->budget_intel ?? [];
                        $when = $record->budget_intel_at->diffForHumans();

                        if (($intel['source_method'] ?? null) === 'document' && ! empty($intel['source_file_path'])) {
                            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($intel['source_file_path']);
                            $name = $intel['source_filename'] ?? 'Source budget document';
                            return new \Illuminate\Support\HtmlString(
                                'Source: <a href="' . e($url) . '" target="_blank" rel="noopener" style="color: #ef4444; text-decoration: underline; font-weight: 500;">' . e($name) . '</a> &middot; run ' . e($when)
                            );
                        }

                        if (! empty($intel['source_url'])) {
                            return new \Illuminate\Support\HtmlString(
                                'Source: <a href="' . e($intel['source_url']) . '" target="_blank" rel="noopener" style="color: #ef4444; text-decoration: underline; font-weight: 500;">public budget document &rarr;</a> &middot; run ' . e($when)
                            );
                        }

                        return 'Snapshot from public budget data, run ' . $when;
                    })
                    ->schema([
                        ViewEntry::make('budget_intel')
                            ->hiddenLabel()
                            ->view('filament.rfp-budget-summary'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => ! empty($record->budget_intel)),

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
            Actions\Action::make('budgetIntel')
                ->label('Budget Intel')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->visible(fn ($record) => $record->score !== null && $record->score >= 60)
                ->modalHeading(fn ($record) => 'Budget Intel — ' . ($record->locality_label ?: 'Unknown locality'))
                ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
                ->modalContent(fn ($record) => view('filament.budget-intel-modal-host', [
                    'screenId' => $record->id,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
            Actions\Action::make('createProposal')
                ->label('Create Proposal')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->visible(fn ($record) => $record->status === 'completed' && $record->proposal_id === null)
                ->requiresConfirmation()
                ->modalHeading('Create Proposal from RFP')
                ->modalDescription(fn ($record) => "Generate a draft proposal from \"{$record->rfp_name}\"? Claude will write an overview and scope items based on the RFP analysis.")
                ->modalSubmitActionLabel('Generate Proposal')
                ->action(function () {
                    $record = $this->record;

                    if ($record->proposal_id !== null) {
                        Notification::make()
                            ->warning()
                            ->title('Proposal already exists')
                            ->body('A proposal has already been generated from this RFP.')
                            ->send();
                        return;
                    }

                    try {
                        $service = new ClaudeService();
                        $content = $service->generateProposalContent(
                            $record->rfp_name ?? 'Untitled RFP',
                            $record->summary ?? '',
                            $record->requirements ?? [],
                            $record->red_flags ?? [],
                        );

                        $proposal = Proposal::create([
                            'user_id' => Auth::id(),
                            'project_title' => $record->rfp_name ?? 'Untitled Project',
                            'proposal_date' => now(),
                            'valid_until' => now()->addDays(60),
                            'client_name' => $content['contact_name'] ?? '',
                            'client_email' => $content['contact_email'] ?? '',
                            'client_company' => $content['contact_company'] ?? '',
                            'introduction' => $content['introduction'] ?? '',
                            'status' => ProposalStatus::Draft,
                            'view_count' => 0,
                        ]);

                        foreach ($content['scope_items'] ?? [] as $i => $item) {
                            $proposal->scopeItems()->create([
                                'category' => $item['category'] ?? 'Development',
                                'title' => $item['title'] ?? '',
                                'description' => $item['description'] ?? '',
                                'bullets' => $item['bullets'] ?? [],
                                'sort_order' => $i,
                            ]);
                        }

                        foreach (TermsLibrary::where('is_active', true)->orderBy('sort_order')->get() as $i => $term) {
                            $proposal->terms()->create([
                                'content' => $term->content,
                                'sort_order' => $i,
                            ]);
                        }

                        $record->update(['proposal_id' => $proposal->id]);

                        Notification::make()
                            ->success()
                            ->title('Draft proposal created')
                            ->body('Review and refine the generated content.')
                            ->send();

                        return redirect(ProposalResource::getUrl('edit', ['record' => $proposal]));
                    } catch (\Throwable $e) {
                        Log::error('Proposal generation from RFP failed', [
                            'rfp_screen_id' => $record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Proposal generation failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('viewProposal')
                ->label('View Proposal')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn ($record) => $record->proposal_id !== null)
                ->url(fn ($record) => ProposalResource::getUrl('edit', ['record' => $record->proposal_id])),
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
