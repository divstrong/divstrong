<?php

namespace App\Filament\Resources;

use App\Enums\ProposalStatus;
use App\Filament\Resources\ProposalResource\Pages;
use App\Mail\ProposalSent;
use App\Models\Proposal;
use App\Models\Client;
use App\Models\ScopeLibrary;
use App\Models\ServiceLibrary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Proposals';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => Auth::id()),

                Tabs::make('Proposal')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        // TAB 1: Cover Page
                        Tab::make('Cover Page')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Client Information')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('client_id')
                                            ->label('Client')
                                            ->relationship('client', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $client = Client::find($state);
                                                    if ($client) {
                                                        $set('client_name', $client->name);
                                                        $set('client_email', $client->email);
                                                        $set('client_company', $client->company);
                                                        $set('client_domain', $client->domain);
                                                    }
                                                }
                                            })
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('company')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('phone')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('domain')
                                                    ->maxLength(255)
                                                    ->prefix('https://'),
                                            ])
                                            ->createOptionUsing(function (array $data, callable $set): int {
                                                $client = Client::create($data);
                                                $set('client_name', $client->name);
                                                $set('client_email', $client->email);
                                                $set('client_company', $client->company);
                                                $set('client_domain', $client->domain);
                                                return $client->id;
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\DatePicker::make('proposal_date')
                                            ->default(now())
                                            ->required(),
                                        Forms\Components\TextInput::make('project_title')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('client_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('client_email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('client_company')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('client_domain')
                                            ->maxLength(255)
                                            ->prefix('https://'),
                                    ]),
                                Section::make('Cover Image')
                                    ->schema([
                                        Forms\Components\FileUpload::make('cover_image')
                                            ->image()
                                            ->directory('proposal-covers')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(5120),
                                    ])
                                    ->collapsible(),
                            ]),

                        // TAB 2: Introduction
                        Tab::make('Introduction')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                Section::make('Project Overview')
                                    ->schema([
                                        Forms\Components\RichEditor::make('introduction')
                                            ->label('Introduction / Overview')
                                            ->toolbarButtons([
                                                'bold', 'italic', 'underline', 'strike',
                                                'h2', 'h3', 'bulletList', 'orderedList',
                                                'link', 'blockquote',
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB 3: Scope of Work
                        Tab::make('Scope of Work')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Scope Items')
                                    ->headerActions([
                                        Action::make('addFromScopeLibrary')
                                            ->label('Import from Library')
                                            ->icon('heroicon-o-book-open')
                                            ->color('warning')
                                            ->form([
                                                Forms\Components\CheckboxList::make('scope_library_ids')
                                                    ->label('Select scope items to import')
                                                    ->options(function () {
                                                        return ScopeLibrary::where('is_active', true)
                                                            ->orderBy('category')
                                                            ->orderBy('sort_order')
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
                                                                $item->id => "[{$item->category}] {$item->title}",
                                                            ]);
                                                    })
                                                    ->searchable()
                                                    ->bulkToggleable()
                                                    ->columns(1),
                                            ])
                                            ->action(function (array $data, $livewire): void {
                                                $items = ScopeLibrary::whereIn('id', $data['scope_library_ids'])->get();
                                                $proposal = $livewire->getRecord();
                                                $currentCount = $proposal->scopeItems()->count();
                                                foreach ($items as $index => $item) {
                                                    $proposal->scopeItems()->create([
                                                        'category' => $item->category,
                                                        'title' => $item->title,
                                                        'description' => $item->description,
                                                        'sort_order' => $currentCount + $index,
                                                    ]);
                                                }
                                                Notification::make()
                                                    ->success()
                                                    ->title(count($data['scope_library_ids']) . ' scope items imported')
                                                    ->send();
                                                $livewire->redirect(Pages\EditProposal::getUrl(['record' => $proposal, 'tab' => 'scope-of-work']));
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditProposal),
                                    ])
                                    ->schema([
                                        Forms\Components\Repeater::make('scopeItems')
                                            ->relationship('scopeItems')
                                            ->orderColumn('sort_order')
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string =>
                                                ($state['category'] ?? '') . ': ' . ($state['title'] ?? 'New Item')
                                            )
                                            ->schema([
                                                Forms\Components\Select::make('category')
                                                    ->options([
                                                        'Design' => 'Design',
                                                        'Development' => 'Development',
                                                        'SEO' => 'SEO',
                                                        'Hosting' => 'Hosting',
                                                        'Content' => 'Content',
                                                        'Maintenance' => 'Maintenance',
                                                    ])
                                                    ->required(),
                                                Forms\Components\TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('description')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Scope Item'),
                                    ]),
                            ]),

                        // TAB 4: Cost / Pricing
                        Tab::make('Cost')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Cost Details')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Textarea::make('cost_notes')
                                            ->label('Notes / Terms')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        Forms\Components\DatePicker::make('valid_until')
                                            ->label('Proposal Valid Until'),
                                    ]),
                                Section::make('Line Items')
                                    ->headerActions([
                                        Action::make('addFromServiceLibrary')
                                            ->label('Import from Service Library')
                                            ->icon('heroicon-o-shopping-bag')
                                            ->color('warning')
                                            ->form([
                                                Forms\Components\CheckboxList::make('service_library_ids')
                                                    ->label('Select services to add')
                                                    ->options(function () {
                                                        return ServiceLibrary::where('is_active', true)
                                                            ->orderBy('category')
                                                            ->orderBy('sort_order')
                                                            ->get()
                                                            ->mapWithKeys(fn ($s) => [
                                                                $s->id => "[{$s->category}] {$s->name} - \${$s->default_price}",
                                                            ]);
                                                    })
                                                    ->searchable()
                                                    ->bulkToggleable()
                                                    ->columns(1),
                                            ])
                                            ->action(function (array $data, $livewire): void {
                                                $services = ServiceLibrary::whereIn('id', $data['service_library_ids'])->get();
                                                $proposal = $livewire->getRecord();
                                                $currentCount = $proposal->costItems()->count();
                                                foreach ($services as $index => $service) {
                                                    $proposal->costItems()->create([
                                                        'description' => $service->name,
                                                        'quantity' => $service->default_quantity,
                                                        'unit_price' => $service->default_price,
                                                        'amount' => $service->default_quantity * $service->default_price,
                                                        'sort_order' => $currentCount + $index,
                                                    ]);
                                                }
                                                Notification::make()
                                                    ->success()
                                                    ->title(count($data['service_library_ids']) . ' services imported')
                                                    ->send();
                                                $livewire->redirect(Pages\EditProposal::getUrl(['record' => $proposal, 'tab' => 'cost']));
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditProposal),
                                    ])
                                    ->schema([
                                        Forms\Components\Repeater::make('costItems')
                                            ->relationship('costItems')
                                            ->orderColumn('sort_order')
                                            ->reorderable()
                                            ->schema([
                                                Forms\Components\TextInput::make('description')
                                                    ->required()
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $set('amount', round((float) $state * (float) $get('unit_price'), 2));
                                                    }),
                                                Forms\Components\TextInput::make('unit_price')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $set('amount', round((float) $get('quantity') * (float) $state, 2));
                                                    }),
                                                Forms\Components\TextInput::make('amount')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->disabled()
                                                    ->dehydrated(),
                                            ])
                                            ->columns(5)
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Line Item'),
                                    ]),
                            ]),

                        // TAB 5: Payment Milestones
                        Tab::make('Milestones')
                            ->icon('heroicon-o-flag')
                            ->schema([
                                Section::make('Payment Milestones')
                                    ->schema([
                                        Forms\Components\Repeater::make('milestones')
                                            ->relationship('milestones')
                                            ->orderColumn('sort_order')
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Milestone')
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                Forms\Components\Textarea::make('description')
                                                    ->rows(2)
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('percentage')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->maxValue(100),
                                                Forms\Components\TextInput::make('amount')
                                                    ->numeric()
                                                    ->prefix('$'),
                                                Forms\Components\TextInput::make('due_description')
                                                    ->label('Due')
                                                    ->placeholder('e.g., Upon project kickoff')
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Milestone'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_company')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ProposalStatus::class),
            ])
            ->actions([
                EditAction::make(),
                Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Proposal')
                    ->modalDescription(fn (Proposal $record) =>
                        "Send proposal to {$record->client_name} at {$record->client_email}?"
                    )
                    ->action(function (Proposal $record) {
                        Mail::to($record->client_email)->send(new ProposalSent($record));
                        $record->update([
                            'status' => ProposalStatus::Sent,
                            'sent_at' => now(),
                        ]);
                        Notification::make()
                            ->success()
                            ->title('Proposal sent to ' . $record->client_email)
                            ->send();
                    })
                    ->visible(fn (Proposal $record) =>
                        in_array($record->status, [ProposalStatus::Draft, ProposalStatus::Sent])
                    ),
                Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Proposal $record) => url("/proposal/{$record->uuid}"))
                    ->openUrlInNewTab(),
                Action::make('copyLink')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->action(function (Proposal $record, $livewire) {
                        $livewire->js(
                            "navigator.clipboard.writeText('" . url("/proposal/{$record->uuid}") . "')"
                        );
                        Notification::make()
                            ->success()
                            ->title('Link copied to clipboard')
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'create' => Pages\CreateProposal::route('/create'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
        ];
    }
}
