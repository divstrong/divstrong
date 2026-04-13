<?php

namespace App\Filament\Resources;

use App\Enums\ProposalStatus;
use App\Filament\Resources\RfpScreenResource\Pages;
use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use App\Models\RfpScreen;
use BackedEnum;
use App\Services\ClaudeService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RfpScreenResource extends Resource
{
    protected static ?string $model = RfpScreen::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('screenah') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Screenah';

    protected static ?string $modelLabel = 'RFP Screen';

    protected static ?string $pluralModelLabel = 'Screenah';

    protected static ?string $slug = 'screenah';

    protected static ?int $navigationSort = 3;

    public static function getDefaultPrompt(): string
    {
        return <<<'PROMPT'
You are an RFP screening analyst for a small, boutique SaaS company. Analyze the following RFP document and determine if it is a good fit for our company to respond to.

We are a small team without enterprise-level resources. Flag any requirements that suggest this RFP is intended for larger firms.

RED FLAGS to look for (score each as present or not):
- 24/7 support or on-call requirements
- Audited financials or SOC 2/SOC 3 compliance requirements
- Deep insurance requirements (high liability minimums, cyber insurance, E&O)
- FedRAMP, FISMA, or government-specific compliance
- Large team staffing requirements (dedicated account managers, on-site staff)
- Multi-year SLA commitments with financial penalties
- Minority/diversity certification requirements (MBE, WBE, DBE)
- Excessive past performance requirements (5+ similar contracts)

RESPOND IN THIS EXACT JSON FORMAT:
```json
{
    "rfp_name": "<short descriptive name for this RFP, e.g. 'City of Austin Website Redesign' or 'DOE Cloud Migration Services'>",
    "due_date": "<response/proposal submission due date in YYYY-MM-DD format, or null if not specified. Look for terms like 'proposals due', 'submission deadline', 'responses must be received by'. Use the final submission deadline, not Q&A or intent-to-bid dates.>",
    "score": <0-100 integer, where 100 = perfect fit for a small SaaS company>,
    "summary": "<2-3 sentence executive summary of the RFP and fit assessment>",
    "red_flags": [
        "<description of each red flag found, or empty array if none>"
    ],
    "requirements": [
        "<key requirement from the RFP that is relevant to our assessment>"
    ]
}
```
PROMPT;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Upload RFP Document')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('rfp_name')
                            ->label('RFP Name')
                            ->placeholder('Optional — will be extracted from document if left blank')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('RFP Document')
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
                            ->helperText('Accepted formats: PDF, DOC, DOCX, TXT, CSV, MD (max 20MB)')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('prompt')
                            ->label('Analysis Prompt')
                            ->default(static::getDefaultPrompt())
                            ->rows(12)
                            ->required()
                            ->helperText('Customize the prompt sent to Claude for analyzing this RFP. The document contents will be appended automatically.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfp_name')
                    ->label('RFP')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Untitled')
                    ->limit(50)
                    ->description(fn (RfpScreen $record) => $record->created_at->format('M j, Y g:i A') . ' by ' . ($record->user?->name ?? 'Unknown'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(function (RfpScreen $record) {
                        if (! $record->due_date) return 'gray';
                        $days = now()->startOfDay()->diffInDays($record->due_date, false);
                        if ($days < 0) return 'danger';
                        if ($days <= 7) return 'warning';
                        return null;
                    })
                    ->description(function (RfpScreen $record) {
                        if (! $record->due_date) return null;
                        $days = (int) now()->startOfDay()->diffInDays($record->due_date, false);
                        if ($days < 0) return abs($days) . ' days overdue';
                        if ($days === 0) return 'Due today';
                        return "in {$days} days";
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Fit Score')
                    ->badge()
                    ->color(fn (RfpScreen $record) => $record->score_badge_color)
                    ->formatStateUsing(fn (RfpScreen $record) => $record->score !== null ? "{$record->score}/100 — {$record->score_label}" : 'Pending')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'analyzing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->actions([
                Action::make('createProposal')
                    ->label('Create Proposal')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->visible(fn (RfpScreen $record) => $record->status === 'completed')
                    ->requiresConfirmation()
                    ->modalHeading('Create Proposal from RFP')
                    ->modalDescription(fn (RfpScreen $record) => "Generate a draft proposal from \"{$record->rfp_name}\"? Claude will write an overview and scope items based on the RFP analysis.")
                    ->modalSubmitActionLabel('Generate Proposal')
                    ->action(function (RfpScreen $record) {
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

                            Notification::make()
                                ->success()
                                ->title('Draft proposal created')
                                ->body("Review and refine the generated content.")
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
                Action::make('reanalyze')
                    ->label('Re-analyze')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Re-analyze this RFP?')
                    ->modalDescription('This will send the document back to Claude for a fresh analysis, overwriting the current results.')
                    ->action(function (RfpScreen $record) {
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
                                'due_date' => $result['due_date'] ?? null,
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
                    }),
                EditAction::make()
                    ->modalWidth('5xl'),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRfpScreens::route('/'),
            'view' => Pages\ViewRfpScreen::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\RfpScreenResource\Widgets\RfpScreenStatsWidget::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }
}
