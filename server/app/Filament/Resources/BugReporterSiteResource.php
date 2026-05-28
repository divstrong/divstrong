<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BugReporterSiteResource\Pages;
use App\Models\BugReporterSite;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BugReporterSiteResource extends Resource
{
    protected static ?string $model = BugReporterSite::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('bug_reports') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Bug Trackers';

    protected static ?string $modelLabel = 'Bug Tracker';

    protected static ?string $pluralModelLabel = 'Bug Trackers';

    protected static ?string $slug = 'bug-reporter-sites';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->options(fn () => Client::orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Internal label, e.g. "Acme Marketing Site"'),
                        Forms\Components\TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('app.example.com')
                            ->helperText('Bare hostname. Reports submitted from this domain are accepted.'),
                        Forms\Components\TextInput::make('notify_email')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Optional. Falls back to the client email.'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),

                Section::make('Embed Snippet')
                    ->columnSpanFull()
                    ->description('Paste this snippet just before </body> on the client site.')
                    ->schema([
                        Forms\Components\Placeholder::make('public_key')
                            ->label('Public Key')
                            ->content(fn ($record) => $record?->public_key ?? '(generated on save)'),
                        Forms\Components\Placeholder::make('embed_snippet')
                            ->label('Embed code')
                            ->content(function (?BugReporterSite $record) {
                                if (! $record) {
                                    return 'Save the site to generate the snippet.';
                                }
                                $src = url('/bug-reporter.js');
                                $snippet = '<script src="'.$src.'" data-site-key="'.$record->public_key.'" defer></script>';
                                $jsString = json_encode($snippet, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
                                $htmlEscaped = e($snippet);

                                return new \Illuminate\Support\HtmlString(
                                    '<div x-data="{copied:false}" style="position:relative;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 14px 14px 16px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:13px;line-height:1.5">'
                                    .'<button type="button" '
                                        .'x-on:click="navigator.clipboard.writeText('.$jsString.').then(()=>{copied=true;setTimeout(()=>copied=false,1500)})" '
                                        .'style="position:absolute;top:8px;right:8px;display:inline-flex;align-items:center;gap:4px;padding:5px 10px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:500;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:6px;cursor:pointer" '
                                        .'x-on:mouseover="$el.style.background=\'#f3f4f6\'" x-on:mouseout="$el.style.background=\'#fff\'">'
                                        .'<svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>'
                                        .'<svg x-show="copied" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                                        .'<span x-text="copied ? \'Copied\' : \'Copy\'"></span>'
                                    .'</button>'
                                    .'<div style="padding-right:90px;white-space:pre-wrap;word-break:break-all;color:#111827">'.$htmlEscaped.'</div>'
                                    .'</div>'
                                );
                            }),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('domain')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bug_reports_count')
                    ->counts('bugReports')
                    ->label('Reports')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                \Filament\Actions\Action::make('rotateKey')
                    ->label('Rotate Key')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription('This invalidates the current embed snippet on every page using it. You will need to re-deploy with the new key.')
                    ->action(function (BugReporterSite $record) {
                        $record->update(['public_key' => BugReporterSite::generateKey()]);
                        Notification::make()
                            ->title('Key rotated')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListBugReporterSites::route('/'),
            'create' => Pages\CreateBugReporterSite::route('/create'),
            'edit' => Pages\EditBugReporterSite::route('/{record}/edit'),
        ];
    }
}
