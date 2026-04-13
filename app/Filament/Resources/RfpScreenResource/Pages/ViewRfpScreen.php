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
                            ->columnSpan(8),
                        TextEntry::make('file_path')
                            ->label('Original RFP')
                            ->formatStateUsing(fn ($record) => $record->original_filename)
                            ->url(fn ($record) => Storage::disk('public')->url($record->file_path))
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('primary')
                            ->columnSpan(8),
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
                ->label('Add Supporting Documents')
                ->icon('heroicon-o-paper-clip')
                ->color('primary')
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
                ->modalSubmitActionLabel('Upload & Re-analyze')
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
                ->label('Re-analyze')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Re-analyze this RFP?')
                ->modalDescription('This will send the RFP and any supporting documents back to Claude for a fresh analysis, overwriting the current results.')
                ->action(fn () => $this->runReanalysis()),
            Actions\DeleteAction::make(),
        ];
    }

    protected function runReanalysis(): void
    {
        $record = $this->record;
        $record->update(['status' => 'analyzing']);

        try {
            $service = new ClaudeService();
            $result = $service->analyzeRfp(
                $record->file_path,
                $record->prompt,
                $record->attachments()->pluck('file_path')->all(),
            );

            $updateData = [
                'score' => $result['score'],
                'due_date' => $result['due_date'] ?? $record->due_date,
                'summary' => $result['summary'],
                'red_flags' => $result['red_flags'],
                'requirements' => $result['requirements'],
                'raw_response' => $result['raw_response'],
                'status' => 'completed',
                'analyzed_at' => now(),
            ];

            if (empty($record->rfp_name) && !empty($result['rfp_name'])) {
                $updateData['rfp_name'] = $result['rfp_name'];
            }

            $record->update($updateData);

            Notification::make()
                ->title("Re-analysis Complete — {$record->score}/100")
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
                ->title('Re-analysis Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->refreshFormData(['rfp_name', 'due_date', 'score', 'summary', 'red_flags', 'requirements', 'raw_response', 'status', 'analyzed_at']);
    }
}
