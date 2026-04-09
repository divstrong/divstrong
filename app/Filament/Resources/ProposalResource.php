<?php

namespace App\Filament\Resources;

use App\Enums\ProposalStatus;
use App\Filament\Resources\ProposalResource\Pages;
use App\Mail\ProposalSent;
use App\Models\Proposal;
use App\Models\Client;
use App\Models\Role;
use App\Models\ScopeLibrary;
use App\Models\ServiceLibrary;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('proposals') ?? false;
    }

    protected static ?string $modelLabel = 'Proposal';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

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
                                            }),
                                        Forms\Components\Select::make('estimator_id')
                                            ->label('Estimator')
                                            ->options(function () {
                                                $estimatorRole = Role::where('name', 'Estimator')->first();
                                                if (! $estimatorRole) {
                                                    return [];
                                                }

                                                return User::where('role_id', $estimatorRole->id)
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->placeholder('Assign an estimator...'),
                                        Forms\Components\DatePicker::make('proposal_date')
                                            ->default(now())
                                            ->required(),
                                        Forms\Components\DatePicker::make('valid_until')
                                            ->label('Proposal Valid Until'),
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
                                        Forms\Components\Select::make('status')
                                            ->options(ProposalStatus::class)
                                            ->default(ProposalStatus::Draft)
                                            ->required(),
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
                                                    ]),
                                                Forms\Components\TextInput::make('title')
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
                                        Forms\Components\Toggle::make('discount_enabled')
                                            ->label('Apply Discount')
                                            ->live()
                                            ->columnSpanFull(),
                                        Forms\Components\Select::make('discount_type')
                                            ->label('Discount Type')
                                            ->options([
                                                'percent' => 'Percentage (%)',
                                                'fixed' => 'Fixed Amount ($)',
                                            ])
                                            ->default('percent')
                                            ->visible(fn (callable $get) => $get('discount_enabled')),
                                        Forms\Components\TextInput::make('discount_value')
                                            ->label('Discount Value')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->suffix(fn (callable $get) => $get('discount_type') === 'percent' ? '%' : '$')
                                            ->visible(fn (callable $get) => $get('discount_enabled')),
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

                        // TAB 5: Roadmap
                        Tab::make('Roadmap')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Section::make('Roadmap Settings')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('roadmap_enabled')
                                            ->label('Include Roadmap in Proposal')
                                            ->live()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('roadmap_title')
                                            ->label('Roadmap Title')
                                            ->default('Operational Transformation Roadmap')
                                            ->maxLength(255)
                                            ->visible(fn (callable $get) => $get('roadmap_enabled')),
                                        Forms\Components\TextInput::make('roadmap_subtitle')
                                            ->label('Subtitle')
                                            ->placeholder('e.g., Phased Implementation Over 12 Months')
                                            ->maxLength(255)
                                            ->visible(fn (callable $get) => $get('roadmap_enabled')),
                                        Forms\Components\TextInput::make('roadmap_months')
                                            ->label('Total Months')
                                            ->numeric()
                                            ->default(12)
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('roadmap_enabled')),
                                        Forms\Components\TextInput::make('roadmap_hours_per_sprint')
                                            ->label('Hours Per Sprint')
                                            ->numeric()
                                            ->default(160)
                                            ->minValue(1)
                                            ->visible(fn (callable $get) => $get('roadmap_enabled')),
                                    ]),
                                Section::make('Phases')
                                    ->visible(fn (callable $get) => $get('roadmap_enabled'))
                                    ->schema([
                                        Forms\Components\Repeater::make('roadmapPhases')
                                            ->relationship('roadmapPhases')
                                            ->orderColumn('sort_order')
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string =>
                                                'Phase ' . (($state['sort_order'] ?? 0) + 1) . ': ' . ($state['title'] ?? 'New Phase')
                                            )
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Phase Title')
                                                    ->required()
                                                    ->placeholder('e.g., Company Hub & Safety')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('subtitle')
                                                    ->placeholder('e.g., Intranet & Safety Training')
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('description')
                                                    ->rows(2)
                                                    ->placeholder('Brief description of this phase...')
                                                    ->columnSpanFull(),
                                                Forms\Components\Select::make('icon')
                                                    ->label('Icon')
                                                    ->options([
                                                        'building' => 'Building / Company',
                                                        'cog' => 'Gear / Settings',
                                                        'clipboard' => 'Clipboard / Operations',
                                                        'handshake' => 'Handshake / CRM',
                                                        'calculator' => 'Calculator / Estimating',
                                                        'truck' => 'Truck / Field Ops',
                                                        'dollar' => 'Dollar / Finance',
                                                        'shield' => 'Shield / Safety',
                                                        'chart' => 'Chart / Analytics',
                                                        'code' => 'Code / Development',
                                                        'globe' => 'Globe / Web',
                                                        'paint' => 'Paint / Design',
                                                        'rocket' => 'Rocket / Launch',
                                                        'users' => 'Users / Team',
                                                        'lock' => 'Lock / Security',
                                                        'database' => 'Database / Data',
                                                        'cloud' => 'Cloud / Hosting',
                                                        'mobile' => 'Mobile / App',
                                                    ])
                                                    ->default('clipboard'),
                                                Forms\Components\ColorPicker::make('color')
                                                    ->label('Phase Color')
                                                    ->default('#3B82F6'),
                                                Forms\Components\TextInput::make('duration_weeks')
                                                    ->label('Duration (weeks)')
                                                    ->numeric()
                                                    ->default(4)
                                                    ->minValue(1),
                                                Forms\Components\TextInput::make('hours')
                                                    ->label('Hours')
                                                    ->numeric()
                                                    ->placeholder('e.g., 160'),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Phase'),
                                    ]),
                            ]),

                        // TAB 6: Payment Milestones
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

                        // TAB 6: Notes
                        Tab::make('Notes')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Livewire::make('proposal-notes', fn ($livewire) => [
                                    'proposalId' => $livewire->getRecord()?->id ?? 0,
                                ])
                                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditProposal),
                                Section::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('notes_placeholder')
                                            ->content('Save the proposal first to access notes.')
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateProposal),
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
                Tables\Columns\TextColumn::make('estimator.name')
                    ->label('Estimator')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
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
                Action::make('copy')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Proposal $record) {
                        $clone = $record->replicate([
                            'uuid', 'status', 'sent_at', 'first_viewed_at',
                            'last_viewed_at', 'view_count', 'accepted_at',
                            'declined_at', 'accepted_ip', 'signature_data',
                            'signature_name', 'cr_signature_name', 'cr_signature_data',
                            'cr_signed_at', 'tc_signature_name', 'tc_signature_data',
                            'tc_signed_at',
                        ]);
                        $clone->status = ProposalStatus::Draft;
                        $clone->view_count = 0;
                        $clone->project_title = $record->project_title . ' (Copy)';
                        $clone->save();

                        foreach ($record->scopeItems as $item) {
                            $clone->scopeItems()->create($item->replicate()->toArray());
                        }
                        foreach ($record->costItems as $item) {
                            $clone->costItems()->create($item->replicate()->toArray());
                        }
                        foreach ($record->milestones as $item) {
                            $clone->milestones()->create($item->replicate()->toArray());
                        }
                        foreach ($record->terms as $item) {
                            $clone->terms()->create($item->replicate()->toArray());
                        }

                        Notification::make()
                            ->success()
                            ->title('Proposal duplicated')
                            ->send();

                        return redirect(ProposalResource::getUrl('edit', ['record' => $clone]));
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forUser();
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
