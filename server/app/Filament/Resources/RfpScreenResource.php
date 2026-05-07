<?php

namespace App\Filament\Resources;

use App\Enums\ProposalStatus;
use App\Filament\Resources\RfpScreenResource\Pages;
use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use App\Models\RfpScreen;
use App\Models\TermsLibrary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
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
You are an RFP screening analyst for divStrong, a small custom web & SaaS development consultancy. We BUILD custom solutions per engagement; we do NOT sell, license, or resell a shelf software product.

We are a small team without enterprise-level resources. Flag any requirements that suggest this RFP is intended for larger firms.

RED FLAGS to look for (score each as present or not):
- COTS / Commercial Off-The-Shelf required — flag ONLY if the RFP explicitly uses the term "COTS" or "Commercial Off-The-Shelf" AND lists it as a requirement. Do NOT infer from generic phrases like "provide software", "describe the software", or "software solution" — those apply to custom builds too. If COTS is offered as one option among others (custom or shelf), do not flag.
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
    "contact_name": "<primary point of contact's full name, or null. Look for 'Contact Person', 'Procurement Officer', 'Submit questions to', etc.>",
    "contact_title": "<contact's job title, or null>",
    "contact_department": "<contact's department or division, or null>",
    "contact_email": "<contact's email address, or null>",
    "contact_phone": "<contact's phone number including extension if listed, or null>",
    "due_date": "<response/proposal submission due date in YYYY-MM-DD format, or null if not specified. Look for terms like 'proposals due', 'submission deadline', 'responses must be received by'. Use the final submission deadline, not Q&A or intent-to-bid dates.>",
    "pre_bid_conference_date": "<date of any pre-bid / pre-proposal / pre-award conference, Q&A session, or vendor info meeting in YYYY-MM-DD format, or null. These sessions typically happen before the due date and allow vendors to ask questions.>",
    "pre_bid_conference_details": "<short description of the pre-bid conference including time, format (in-person/virtual), location or meeting link, and whether attendance is mandatory or optional. Null if no conference is listed.>",
    "score": <0-100 integer, where 100 = perfect fit for a small SaaS company>,
    "summary": "<2-3 sentence executive summary of the RFP and fit assessment>",
    "red_flags": [
        "<description of each red flag found, or empty array if none>"
    ],
    "requirements": [
        "<key requirement from the RFP that is relevant to our assessment>"
    ],
    "submission_requirements": [
        "<a specific instruction on HOW the proposal must be submitted or formatted — e.g. 'Submit 1 original and 5 hard copies to [address] by [time]', 'Response must not exceed 40 pages', 'Use provided forms in Appendix A', 'Include signed non-collusion affidavit', 'Sealed envelope marked with RFP number'. Extract every submission/formatting/packaging requirement you can find. Many RFPs dedicate an entire section (e.g. 'Proposal Preparation' or 'Response Format') to this — capture those items. Empty array if none specified.>"
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
                            ->live()
                            ->helperText('Accepted formats: PDF, DOC, DOCX, TXT, CSV, MD (max 20MB)')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('rfp_name')
                            ->label('RFP Name')
                            ->placeholder('Optional — will be extracted from document if left blank')
                            ->maxLength(255)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('file_path')))
                            ->columnSpanFull(),
                    ]),
                Section::make('Analysis Prompt')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->description('Customize the prompt sent to Claude. Leave as-is for the standard RFP screening analysis.')
                    ->schema([
                        Forms\Components\Textarea::make('prompt')
                            ->hiddenLabel()
                            ->default(static::getDefaultPrompt())
                            ->rows(12)
                            ->required()
                            ->helperText('The document contents will be appended automatically.')
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
                    ->description(fn (RfpScreen $record) => $record->scanned_with_model_label ? 'Scanned with ' . $record->scanned_with_model_label : null)
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->filters([
                Tables\Filters\SelectFilter::make('fit_score')
                    ->label('Fit Score')
                    ->options([
                        'great' => 'Great Fit (75+)',
                        'good' => 'Good Fit (60–74)',
                        'not_us' => 'Not Us (<60)',
                        'pending' => 'Pending',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'great' => $query->where('score', '>=', 75),
                            'good' => $query->whereBetween('score', [60, 74]),
                            'not_us' => $query->whereNotNull('score')->where('score', '<', 60),
                            'pending' => $query->whereNull('score'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Action::make('reanalyze')
                    ->label('Rescan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->modalHeading('Rescan this RFP?')
                    ->modalContent(fn (RfpScreen $record) => view('filament.rfp-rescan-modal-host', [
                        'screenId' => $record->id,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
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
