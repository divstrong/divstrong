<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The editable copy behind the three outreach emails.
 *
 * Grouped under Prospects in the sidebar because that is the only place these are sent
 * from — a template with no prospect to send it to is not a thing this app has.
 */
class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    // Nested under Prospects in the sidebar, matching how the proposal libraries hang off
    // Proposals. Must match ProspectResource's navigation label exactly.
    protected static ?string $navigationParentItem = 'Prospects';

    protected static ?string $slug = 'email-templates';

    protected static ?string $navigationLabel = 'Email Templates';

    protected static ?string $modelLabel = 'Email Template';

    protected static ?string $pluralModelLabel = 'Email Templates';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('email_templates') ?? false;
    }

    /**
     * Editor on the left, live preview on the right.
     *
     * Editing email HTML with no sight of the result means every change is a guess followed
     * by a test send. The preview renders through the real mail shell, so what is on screen
     * is what a recipient gets — header, footer, compliance block and all.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            // The mailables look templates up by key; changing it silently
                            // detaches the template and the app falls back to Blade.
                            ->disabled(fn (?EmailTemplate $record) => $record !== null)
                            ->dehydrated()
                            ->helperText(fn (?EmailTemplate $record) => $record
                                ? 'Locked — the code looks this template up by key.'
                                : 'Machine key used by the code, e.g. agency_intro.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('When off, the app falls back to the built-in version of this email.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Grid::make(['default' => 1, 'xl' => 5])
                    ->schema([
                        Section::make('Content')
                            ->columnSpan(['default' => 1, 'xl' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('subject')
                                    ->required()
                                    ->maxLength(255)
                                    // Feeds the preview's subject line as you type.
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('placeholders')
                                    ->label('Available placeholders')
                                    ->content(fn (?EmailTemplate $record) => $record?->description
                                        ?: 'Use {{ placeholder }} for values, and {{#notes}}…{{/notes}} to show a block only when that value is present.')
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('body')
                                    ->required()
                                    ->columnSpanFull()
                                    // onBlur rather than on every keystroke: the preview
                                    // re-renders the whole mail shell, and doing that per
                                    // character makes the editor feel sticky.
                                    ->live(onBlur: true)
                                    ->helperText('Rendered inside the branded divStrong header and footer — do not add your own. The greeting and sign-off are added at send time.'),
                            ]),

                        Section::make()
                            ->columnSpan(['default' => 1, 'xl' => 2])
                            ->schema([
                                Forms\Components\ViewField::make('preview')
                                    ->hiddenLabel()
                                    ->dehydrated(false)
                                    // The view reads subject and body off the form container,
                                    // so it needs no state of its own.
                                    ->view('filament.prospects.template-preview'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (EmailTemplate $record) => $record->key),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('editor.name')
                    ->label('Last edited by')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    /**
     * Templates are seeded by `php artisan email:seed-templates` and looked up by key from
     * code, so a template created here would be one nothing ever sends.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
