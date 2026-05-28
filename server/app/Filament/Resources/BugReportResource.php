<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BugReportResource\Pages;
use App\Models\BugReport;
use App\Models\BugReporterSite;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BugReportResource extends Resource
{
    protected static ?string $model = BugReport::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('bug_reports') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Bugs';

    protected static ?string $modelLabel = 'Bug';

    protected static ?string $pluralModelLabel = 'Bugs';

    protected static ?string $slug = 'bug-reports';

    protected static ?int $navigationSort = 11;

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->hasPermission('bug_reports')) {
            return null;
        }

        $count = BugReport::where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('bug_reporter_site_id')
                            ->label('Site')
                            ->options(fn () => BugReporterSite::orderBy('name')->pluck('name', 'id')->all())
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('url')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('what_happened')
                            ->label('What Happened?')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('reporter_email')
                            ->label('Reporter Email')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Placeholder::make('viewport')
                            ->label('Viewport')
                            ->content(fn (?BugReport $record) => $record
                                ? trim(($record->viewport_width ?? '?').' × '.($record->viewport_height ?? '?'))
                                : '—'),
                        Forms\Components\TextInput::make('referrer')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Screenshot')
                    ->schema([
                        Forms\Components\Placeholder::make('screenshot_preview')
                            ->hiddenLabel()
                            ->content(function (?BugReport $record) {
                                if (! $record?->screenshot_path) {
                                    return 'No screenshot.';
                                }
                                $url = route('bug-screenshot.show', $record);

                                return new \Illuminate\Support\HtmlString(
                                    '<a href="'.e($url).'" target="_blank" rel="noopener">'
                                    .'<img src="'.e($url).'" alt="Screenshot" style="max-width:100%;border:1px solid #e5e7eb;border-radius:6px" />'
                                    .'</a>'
                                );
                            }),
                    ])
                    ->visible(fn (?BugReport $record) => (bool) $record?->screenshot_path),

                Section::make('Console Errors')
                    ->schema([
                        Forms\Components\Placeholder::make('console_errors_view')
                            ->hiddenLabel()
                            ->content(function (?BugReport $record) {
                                $errors = $record?->console_errors ?? [];
                                if (empty($errors)) {
                                    return 'None captured.';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<pre style="white-space:pre-wrap;background:#f9fafb;padding:12px;border-radius:6px;font-size:12px;max-height:300px;overflow:auto">'
                                    .e(implode("\n\n", array_map(fn ($e) => is_string($e) ? $e : json_encode($e), $errors)))
                                    .'</pre>'
                                );
                            }),
                    ]),

                Section::make('Triage')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(array_combine(BugReport::STATUSES, BugReport::STATUSES))
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options(array_combine(BugReport::PRIORITIES, BugReport::PRIORITIES))
                            ->required()
                            ->helperText('Set by staff after triage.'),
                        Forms\Components\Select::make('severity')
                            ->options(array_combine(BugReport::SEVERITIES, BugReport::SEVERITIES))
                            ->required()
                            ->helperText('Reported by the user.'),
                        Forms\Components\Textarea::make('internal_notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('what_happened')
                    ->label('What Happened')
                    ->wrap()
                    ->limit(140)
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'wontfix',
                        'warning' => 'new',
                        'info' => 'triaged',
                        'primary' => 'in_progress',
                        'success' => 'resolved',
                    ]),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity (reporter)')
                    ->badge()
                    ->colors([
                        'gray' => 'unsure',
                        'info' => 'low',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\IconColumn::make('screenshot_path')
                    ->label('Shot')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_combine(BugReport::STATUSES, BugReport::STATUSES)),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(array_combine(BugReport::PRIORITIES, BugReport::PRIORITIES)),
                Tables\Filters\SelectFilter::make('severity')
                    ->options(array_combine(BugReport::SEVERITIES, BugReport::SEVERITIES)),
                Tables\Filters\SelectFilter::make('bug_reporter_site_id')
                    ->label('Site')
                    ->options(fn () => BugReporterSite::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListBugReports::route('/'),
            'edit' => Pages\EditBugReport::route('/{record}/edit'),
        ];
    }
}
