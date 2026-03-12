<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RfpScreenResource\Pages;
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

    protected static ?string $navigationParentItem = 'Proposals';

    protected static ?string $navigationLabel = 'Screenah';

    protected static ?string $modelLabel = 'RFP Screen';

    protected static ?string $pluralModelLabel = 'Screened RFPs';

    protected static ?string $slug = 'screenah';

    protected static ?int $navigationSort = 6;

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
- Requiring physical office locations or geographic presence
- Bonds or financial guarantees
- Minority/diversity certification requirements (MBE, WBE, DBE)
- Excessive past performance requirements (5+ similar contracts)

RESPOND IN THIS EXACT JSON FORMAT:
```json
{
    "rfp_name": "<short descriptive name for this RFP, e.g. 'City of Austin Website Redesign' or 'DOE Cloud Migration Services'>",
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
                    ->description(fn (RfpScreen $record) => $record->created_at->format('M j, Y g:i A'))
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('By')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
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
                            $result = $service->analyzeRfp($record->file_path, $record->prompt);

                            $updateData = [
                                'score' => $result['score'],
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forUser();
    }
}
