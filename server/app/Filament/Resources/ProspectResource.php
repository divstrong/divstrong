<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProspectResource\Pages;
use App\Mail\AgencyIntro;
use App\Mail\ClientIntro;
use App\Mail\GeneralUpdate;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use App\Support\CampaignRunner;
use App\Support\ProspectMailer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static ?string $slug = 'prospects';

    protected static ?string $navigationLabel = 'Prospects';

    protected static ?string $modelLabel = 'Prospect';

    protected static ?string $pluralModelLabel = 'Prospects';

    // After Portfolio, before Bugs — the top of the sales funnel, ahead of Clients' own
    // section only in reading order, not in importance.
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('prospects') ?? false;
    }

    /**
     * The US states, in one place. Repeated verbatim on the prospect form and nowhere else
     * worth extracting to — but long enough that inlining it twice would bury the fields
     * either side of it.
     *
     * @return array<string, string>
     */
    public static function stateOptions(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
        ];
    }

    /**
     * Owner options for the "Assigned To" selects: users who can actually reach the panel.
     * No point assigning a prospect to someone who cannot log in to work it.
     *
     * Public because the import and discovery modals on the list page offer the same choice.
     *
     * @return array<int, string>
     */
    public static function panelUserOptions(): array
    {
        return User::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    /**
     * Existing source tags, for the datalist on every field that writes one.
     *
     * @return array<int, string>
     */
    public static function sourceOptions(): array
    {
        return Prospect::query()
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->pluck('source')
            ->toArray();
    }

    /**
     * One column of form, one column of history.
     *
     * The timeline is the thing you actually want in view while writing a note or changing
     * status, so it sits beside the form rather than behind a tab. On create there is no
     * timeline yet, so the right column hides and the form takes the full width rather
     * than leaving a hole.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])
                    ->schema([
                        Group::make()
                            ->columnSpan(['default' => 1, 'xl' => 2])
                            ->schema([
                                Section::make('Tracking')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                Forms\Components\Select::make('segment')
                                                    ->label('Segment')
                                                    ->options(fn () => Prospect::segments())
                                                    ->default(Prospect::SEGMENT_AGENCY)
                                                    ->required()
                                                    ->helperText('Which story fits them — it picks the default email.'),

                                                Forms\Components\Select::make('status')
                                                    ->options(fn () => Prospect::statuses())
                                                    ->default('new')
                                                    ->required(),

                                                Forms\Components\Select::make('priority')
                                                    ->label('Probability')
                                                    ->options([
                                                        0 => '0%',
                                                        25 => '25%',
                                                        50 => '50%',
                                                        75 => '75%',
                                                        100 => '100%',
                                                    ])
                                                    ->default(0),

                                                Forms\Components\Select::make('assigned_to')
                                                    ->label('Assigned To')
                                                    ->options(fn () => static::panelUserOptions())
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(fn () => auth()->id()),
                                            ]),

                                        Forms\Components\TextInput::make('source')
                                            ->label('Source')
                                            ->helperText('Segment tag for the batch, e.g. "Discovery Sep 8".')
                                            ->maxLength(255)
                                            ->datalist(fn () => static::sourceOptions()),
                                    ]),

                                Section::make('Website concept')
                                    ->description('The design built for this prospect. A preview URL is what makes them eligible for the preview campaign — without one the emails have nothing to point at.')
                                    ->collapsed(fn (?Prospect $record) => blank($record?->preview_url))
                                    ->schema([
                                        Forms\Components\TextInput::make('preview_url')
                                            ->label('Preview URL')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://www.divstrong.com/theirshop/')
                                            ->helperText('Where their tailored design is deployed.'),

                                        Forms\Components\FileUpload::make('preview_image')
                                            ->label('Screenshot')
                                            ->image()
                                            ->disk('public')
                                            ->directory('preview-shots')
                                            ->imageEditor()
                                            ->maxSize(4096)
                                            ->helperText('Optional. Used as the hero of the campaign email and on their landing page — a picture of the thing sells it far better than a link.'),

                                        Forms\Components\Placeholder::make('preview_landing')
                                            ->label('Their landing page')
                                            ->content(fn (?Prospect $record) => $record?->previewLandingUrl()
                                                ? new \Illuminate\Support\HtmlString(
                                                    '<a href="'.e($record->previewLandingUrl()).'" target="_blank" class="text-primary-600 underline">'
                                                    .e($record->previewLandingUrl()).'</a>'
                                                    .'<span class="block text-xs text-gray-500 mt-1">Tracked page with the design, both questions and the calendar. This is what the campaign emails link to.</span>'
                                                )
                                                : 'Save a preview URL to generate this.')
                                            ->visible(fn (?Prospect $record) => $record?->exists ?? false),

                                        Forms\Components\Placeholder::make('preview_comments_shown')
                                            ->label('In their words')
                                            ->content(fn (?Prospect $record) => filled($record?->preview_comments)
                                                ? new \Illuminate\Support\HtmlString(nl2br(e($record->preview_comments)))
                                                : '—')
                                            ->columnSpanFull()
                                            ->visible(fn (?Prospect $record) => filled($record?->preview_comments)),

                                        Forms\Components\Placeholder::make('preview_answers')
                                            ->label('What they said')
                                            ->content(fn (?Prospect $record) => match (true) {
                                                ! $record?->exists => '—',
                                                $record->interested === true => 'Likes it and is interested — booked: '.($record->hasBookedMeeting() ? 'yes' : 'not yet'),
                                                $record->interested === false => 'Not interested in switching',
                                                $record->likes_design === true => 'Likes the design, not yet answered on switching',
                                                $record->likes_design === false => 'Did not like the direction',
                                                $record->preview_viewed_at !== null => 'Opened the page, no answer yet',
                                                default => 'Not opened yet',
                                            })
                                            ->visible(fn (?Prospect $record) => $record?->exists ?? false),
                                    ])
                                    ->columns(2),

                                Section::make('Contact')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('phone')
                                                    ->tel()
                                                    ->maxLength(255),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('company')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('title')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('website')
                                                    ->url()
                                                    ->maxLength(255),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('address1')
                                                    ->label('Address Line 1')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('address2')
                                                    ->label('Address Line 2')
                                                    ->maxLength(255),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('city')
                                                    ->maxLength(255),

                                                Forms\Components\Select::make('state')
                                                    ->options(fn () => static::stateOptions())
                                                    ->searchable(),

                                                Forms\Components\TextInput::make('zip')
                                                    ->maxLength(255),
                                            ]),
                                    ]),

                                Section::make('Notes')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->hiddenLabel()
                                            ->rows(8)
                                            ->columnSpanFull()
                                            ->placeholder('Conversations, follow-ups, what they said they needed...'),
                                    ]),
                            ]),

                        Group::make()
                            ->columnSpan(['default' => 1, 'xl' => 1])
                            // No timeline exists before the record does.
                            ->visible(fn (?Prospect $record) => $record !== null)
                            ->schema([
                                Section::make('Activity')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        View::make('filament.prospects.activity')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->date('M j, Y')
                    // Full timestamp on hover: two prospects added the same day still have
                    // an order, and this is the default sort.
                    ->tooltip(fn (Prospect $record) => $record->created_at?->format('M j, Y g:i A'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name / Company')
                    ->searchable(['name', 'company'])
                    ->sortable()
                    ->placeholder('—')
                    // Agency names run long ("Northbound Creative Collective LLC") and one of
                    // them was setting the width of the whole column.
                    ->limit(26)
                    ->grow(false)
                    ->width('1%')
                    ->description(fn (Prospect $record) => Str::limit((string) $record->company, 30))
                    // Only when something was actually cut — a tooltip repeating what is
                    // already fully visible is noise.
                    ->tooltip(function (Prospect $record): ?string {
                        $name = (string) $record->name;
                        $company = (string) $record->company;

                        if (mb_strlen($name) <= 26 && mb_strlen($company) <= 30) {
                            return null;
                        }

                        return trim($name.($company !== '' ? ' · '.$company : ''));
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->placeholder('—')
                    // Engagement rides under the address rather than owning a column. In its
                    // own narrow column the chips stacked one per line and ate three rows of
                    // height; here they sit in a single row with space to spare.
                    ->description(fn (Prospect $record) => new HtmlString(
                        view('filament.prospects.engagement', [
                            'getState' => fn () => $record,
                        ])->render(),
                    )),

                Tables\Columns\TextColumn::make('segment')
                    ->label('Segment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Prospect::segments()[$state] ?? 'Agency')
                    ->color(fn (?string $state) => match ($state) {
                        Prospect::SEGMENT_CLIENT => 'info',
                        Prospect::SEGMENT_GENERAL => 'warning',
                        default => 'success',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Owner')
                    ->placeholder('Unassigned')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('called_at')
                    ->label('Called')
                    ->alignCenter()
                    // Cast to a boolean first: IconColumn renders nothing at all for a null
                    // state, so an uncalled prospect showed an empty cell rather than an icon.
                    ->getStateUsing(fn (Prospect $record): bool => $record->called_at !== null)
                    // Grey cross for no, blue phone for yes — the shape carries the answer as
                    // well as the colour, so it survives being printed or colour-blind.
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-phone' : 'heroicon-m-x-mark')
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray')
                    ->tooltip(fn (Prospect $record): string => $record->called_at
                        ? 'Called '.$record->called_at->diffForHumans().' — click to undo'
                        : 'Not called yet — click to mark as called')
                    // The column itself is the action. No confirmation: one click sets it, the
                    // same click unsets it, so a misclick costs nothing.
                    ->action(fn (Prospect $record) => $record->update([
                        'called_at' => $record->called_at ? null : now(),
                    ]))
                    ->toggleable(),

                Tables\Columns\SelectColumn::make('lead_status')
                    ->label('Lead Status')
                    ->options(fn () => Prospect::leadStatuses())
                    // No blank option: every prospect has a triage state, and "unqualified"
                    // is what not-yet-assessed means.
                    ->selectablePlaceholder(false)
                    ->rules(['required', 'in:qualified,unqualified,dismissed'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('campaign')
                    ->label('Campaign')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        str_contains($state, 'Booked') => 'success',
                        str_contains($state, 'Stopped') || str_contains($state, 'Not interested') => 'danger',
                        str_contains($state, 'Done') => 'gray',
                        default => 'info',
                    })
                    ->state(function (Prospect $record): ?string {
                        $enrollment = $record->enrollments()->latest('id')->first();

                        if (! $enrollment) {
                            return null;
                        }

                        $step = $enrollment->last_step_position ?? 0;

                        return match ($enrollment->status) {
                            CampaignEnrollment::STATUS_ACTIVE => 'Step '.$step.' · next '
                                .($enrollment->next_send_at?->diffForHumans() ?? 'unscheduled'),
                            CampaignEnrollment::STATUS_COMPLETED => 'Done · '.$step.' sent',
                            default => CampaignEnrollment::stopReasons()[$enrollment->stop_reason] ?? 'Stopped',
                        };
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('preview_url')
                    ->label('Preview')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-minus-small')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->url(fn (Prospect $record): ?string => $record->previewLandingUrl())
                    ->openUrlInNewTab()
                    ->tooltip(fn (Prospect $record): ?string => $record->preview_url)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('segment')
                    ->label('Segment')
                    ->options(fn () => Prospect::segments()),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Owner')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('called_at')
                    ->label('Called')
                    ->placeholder('Any')
                    ->trueLabel('Called')
                    ->falseLabel('Not called')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('called_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('called_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                // Defaults to hiding dismissed leads. Binned prospects are the one triage
                // state nobody wants in the working list, so they stay out of it unless
                // somebody goes looking. One control rather than a separate "show dismissed"
                // toggle: a toggle and a status select would fight each other the moment
                // someone picked Dismissed with the toggle still hiding them.
                Tables\Filters\SelectFilter::make('lead_status')
                    ->label('Lead Status')
                    ->options([
                        'active' => 'Qualified + Unqualified',
                        Prospect::LEAD_QUALIFIED => 'Qualified',
                        Prospect::LEAD_UNQUALIFIED => 'Unqualified',
                        Prospect::LEAD_DISMISSED => 'Dismissed',
                    ])
                    ->default('active')
                    // Clearing the filter is how you see everything, dismissed included.
                    ->placeholder('All (including dismissed)')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            // orWhereNull: a row with no triage state yet is not dismissed.
                            'active' => $query->where(fn (Builder $q) => $q
                                ->where('lead_status', '!=', Prospect::LEAD_DISMISSED)
                                ->orWhereNull('lead_status')),
                            null, '' => $query,
                            default => $query->where('lead_status', $data['value']),
                        };
                    }),

                Tables\Filters\SelectFilter::make('source')
                    ->options(fn () => Prospect::query()
                        ->whereNotNull('source')
                        ->where('source', '!=', '')
                        ->distinct()
                        ->pluck('source', 'source')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('engagement')
                    ->label('Engagement')
                    ->options([
                        'clicked' => 'Clicked a link',
                        // "Opened" is every prospect who opened, clickers included, so it
                        // matches the Opened stat tile that links here. The stalled ones get
                        // their own option below rather than quietly redefining this one.
                        'opened' => 'Opened',
                        'opened_no_click' => 'Opened, no click yet',
                        'sent_no_open' => 'Emailed, never opened',
                        'never_emailed' => 'Never emailed',
                        'bounced' => 'Bounced',
                        'unsubscribed' => 'Unsubscribed',
                    ])
                    // Derived from the activity timeline, so each option is its own subquery
                    // rather than a column comparison.
                    ->query(function (Builder $query, array $data): Builder {
                        $has = fn (string $type) => fn ($q) => $q->where('type', $type);

                        return match ($data['value'] ?? null) {
                            'clicked' => $query->whereHas('activities', $has(ProspectActivity::EMAIL_CLICKED)),
                            'opened' => $query->whereHas('activities', $has(ProspectActivity::EMAIL_OPENED)),
                            // Opened and stalled there. A click already implies an open, so
                            // this is the cut that surfaces who is worth chasing.
                            'opened_no_click' => $query
                                ->whereHas('activities', $has(ProspectActivity::EMAIL_OPENED))
                                ->whereDoesntHave('activities', $has(ProspectActivity::EMAIL_CLICKED)),
                            'sent_no_open' => $query
                                ->whereHas('activities', $has(ProspectActivity::EMAIL_SENT))
                                ->whereDoesntHave('activities', $has(ProspectActivity::EMAIL_OPENED)),
                            'never_emailed' => $query->whereDoesntHave('activities', $has(ProspectActivity::EMAIL_SENT)),
                            // Only prospects whose latest send bounced — a successful re-send
                            // drops them out, matching what the chip shows.
                            'bounced' => $query->currentlyBounced(),
                            // Not an engagement state so much as the end of engagement, but
                            // this is where someone looks to ask "who can I still email".
                            'unsubscribed' => $query->whereNotNull('unsubscribed_at'),
                            default => $query,
                        };
                    }),

                // Two selects working together: which email, and whether it has gone out.
                // Kept separate from the Engagement filter above — that one asks "did they
                // react to anything", this one asks "who still needs this specific email",
                // which is what you need to work a list without double-sending.
                Tables\Filters\Filter::make('email_status')
                    ->schema([
                        Forms\Components\Select::make('label')
                            ->label('Email')
                            ->placeholder('Any email')
                            ->options(fn () => array_combine(
                                array_keys(Prospect::EMAIL_LABELS),
                                array_keys(Prospect::EMAIL_LABELS),
                            )),

                        Forms\Components\Select::make('state')
                            // Not just 'Status': the pipeline filter owns that word, and
                            // stacked in one column the two would read as a repeat.
                            ->label('Delivery status')
                            ->placeholder('Any status')
                            ->default('not_sent')
                            ->options([
                                'not_sent' => 'Not sent',
                                'sent' => 'Sent',
                                'opened' => 'Opened',
                                'clicked' => 'Clicked',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $label = $data['label'] ?? null;
                        $state = $data['state'] ?? null;

                        // Both halves are needed; a status with no email chosen would just
                        // duplicate the Engagement filter.
                        if (blank($label) || blank($state)) {
                            return $query;
                        }

                        $type = match ($state) {
                            'opened' => ProspectActivity::EMAIL_OPENED,
                            'clicked' => ProspectActivity::EMAIL_CLICKED,
                            default => ProspectActivity::EMAIL_SENT,
                        };

                        // meta->label is how ProspectMailer tags each send, and how the
                        // webhook tags the events that follow it.
                        $matches = fn ($q) => $q->where('type', $type)->where('meta->label', $label);

                        return $state === 'not_sent'
                            ? $query->whereDoesntHave('activities', $matches)
                            : $query->whereHas('activities', $matches);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['label'] ?? null) || blank($data['state'] ?? null)) {
                            return null;
                        }

                        $states = [
                            'not_sent' => 'not sent',
                            'sent' => 'sent',
                            'opened' => 'opened',
                            'clicked' => 'clicked',
                        ];

                        return $data['label'].': '.($states[$data['state']] ?? $data['state']);
                    })
                    // Its two selects are a pair, so they sit side by side across the full
                    // width rather than being split across the outer grid.
                    ->columns(2)
                    ->columnSpanFull(),

                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => Prospect::statuses()),

                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_to'))
                    ->toggle(),

                Tables\Filters\Filter::make('converted')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('client_id'))
                    ->toggle(),
            ])
            // Nine filters in a dropdown get clipped by the table container and run off the
            // bottom of the viewport. A modal is not bound by that container, so it can be
            // wide enough to read and tall enough to hold the lot without scrolling.
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth(Width::TwoExtraLarge)
            // Two across at this width, in working order: segment, owner, where it came
            // from, how it is engaging, then the narrowing toggles.
            ->filtersFormColumns(2)
            ->recordActions([
                // Every action in this group sends commercial email. Once someone has opted
                // out, ProspectMailer refuses them anyway, but leaving a live Send button on
                // the row invites a rep to try, get no error and no email, and conclude the
                // tool is broken. Hidden is the honest state.
                ActionGroup::make([...static::emailActions(), ...static::campaignStepActions()])
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('gray')
                    ->button()
                    ->tooltip('Send one of the outreach emails')
                    ->hidden(fn (Prospect $record) => $record->isUnsubscribed()),

                static::enrollAction(),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::enrollBulkAction(),
                ]),
                BulkActionGroup::make([
                    BulkAction::make('assignOwner')
                        ->label('Assign owner')
                        ->icon('heroicon-o-user-plus')
                        ->schema([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Owner')
                                ->options(fn () => static::panelUserOptions())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each->update(['assigned_to' => $data['assigned_to']]);

                            Notification::make()
                                ->success()
                                ->title('Owner assigned')
                                ->body($records->count().' prospect(s) reassigned.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('setSegment')
                        ->label('Set segment')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Forms\Components\Select::make('segment')
                                ->label('Segment')
                                ->options(fn () => Prospect::segments())
                                ->required()
                                ->helperText('Changes which email the row offers first. Nothing is sent.'),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each->update(['segment' => $data['segment']]);

                            Notification::make()
                                ->success()
                                ->title('Segment updated')
                                ->body($records->count().' prospect(s) re-segmented.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            // The chips and the row actions ask emailEngagement()/hasSentEmail() per
            // prospect. Without this the page costs four extra queries per row just to
            // decide which chips to draw.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('checklistActivities'));
    }

    /**
     * The three outreach emails, as actions.
     *
     * Shared verbatim between the list row and the edit page, because a rep who opens a
     * prospect to read the timeline before writing is doing the right thing and should not
     * find a different set of buttons there.
     *
     * `$hideSent` is the difference: on the list this behaves as a checklist and a sent
     * email drops out of the menu, so working down a page never double-sends. On the edit
     * page every email stays available, because deliberately re-sending one to a corrected
     * address is a thing people do and there is nowhere else to do it.
     *
     * @return array<int, Action>
     */
    public static function emailActions(bool $hideSent = true): array
    {
        return [
            static::emailAction(
                name: 'sendAgencyIntro',
                label: 'Agency Intro',
                icon: 'heroicon-o-building-office-2',
                mailable: AgencyIntro::class,
                description: 'The white-label pitch: we are the development bench they do not '
                    .'have, invisible behind their brand, priced so there is margin left when '
                    .'they mark it up. Signed by you, and replies come back to your address. '
                    .'Edit the wording under Email Templates → Agency Intro.',
                hideSent: $hideSent,
            ),

            static::emailAction(
                name: 'sendClientIntro',
                label: 'Client Intro',
                icon: 'heroicon-o-rocket-launch',
                mailable: ClientIntro::class,
                description: 'The direct pitch: AI-enabled delivery, so scoping comes back in '
                    .'hours and builds land in days rather than weeks. For companies that would '
                    .'buy the work themselves rather than resell it. Edit the wording under '
                    .'Email Templates → Client Intro.',
                hideSent: $hideSent,
            ),

            static::emailAction(
                name: 'sendGeneralUpdate',
                label: 'General Update',
                icon: 'heroicon-o-megaphone',
                mailable: GeneralUpdate::class,
                description: 'The open one — news, a launch, an event, an offer. Rewrite the body '
                    .'under Email Templates → General Update before sending, or put the whole '
                    .'message in the personal note below.',
                // Never hidden: this is the one email that gets sent more than once by
                // design, because next month's news is not this month's.
                hideSent: false,
            ),
        ];
    }

    /**
     * The campaign emails, sendable one at a time.
     *
     * The drip owns the timing, but the steps are still ordinary emails and there are real
     * reasons to fire one by hand: a prospect whose preview was only just built, a reply
     * asking for the detail that step three covers, or simply wanting to see one arrive
     * before trusting the scheduler with a hundred of them.
     *
     * Sending one keeps the enrolment in step — see CampaignRunner::sendStepNow().
     *
     * @return array<int, Action>
     */
    public static function campaignStepActions(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('campaign_steps')) {
            return [];
        }

        return CampaignStep::query()
            ->where('is_active', true)
            ->whereHas('campaign', fn ($q) => $q->where('is_active', true))
            ->with('campaign')
            ->orderBy('campaign_id')
            ->orderBy('position')
            ->get()
            ->map(fn (CampaignStep $step) => static::campaignStepAction($step))
            ->all();
    }

    protected static function campaignStepAction(CampaignStep $step): Action
    {
        $label = $step->position . '. ' . $step->name;

        return Action::make('sendStep' . $step->id)
            ->label($label)
            ->icon('heroicon-o-rectangle-stack')
            ->color('gray')
            ->modalHeading('Send "' . $label . '"')
            ->modalDescription(fn (?Prospect $record): string => 'From ' . $step->campaign->name
                . '. It links to the preview page built for them, and sending it here moves '
                . 'their place in the sequence forward so the scheduler does not send it again.')
            ->modalSubmitActionLabel('Send now')
            // Needs a preview built, because that is the whole content of the email.
            ->hidden(fn (?Prospect $record): bool => $record === null
                || $record->isUnsubscribed()
                || (CampaignRunner::needsPreview($step->campaign) && ! $record->hasPreview()))
            ->action(function (Prospect $record) use ($step, $label) {
                try {
                    CampaignRunner::sendStepNow($record, $step);
                } catch (\RuntimeException $e) {
                    Notification::make()->warning()->title('Not sent')->body($e->getMessage())->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title($label . ' sent')
                    ->body('Sent to ' . $record->email)
                    ->send();
            });
    }

    /**
     * One outreach email action. All three are the same shape — pick recipients, optionally
     * add a note, send, timeline it — so they are built rather than written out three times.
     *
     * @param  class-string<\App\Mail\ProspectSalesMail>  $mailable
     */
    protected static function emailAction(
        string $name,
        string $label,
        string $icon,
        string $mailable,
        string $description,
        bool $hideSent,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            // The email matching this prospect's segment is picked out in the panel's red, so
            // working down a mixed list does not mean re-reading three menu items every row to
            // remember which story fits an agency and which fits a direct buyer. It is a hint,
            // not a lock — all three stay clickable.
            ->color(fn (?Prospect $record) => $record && Prospect::segmentEmailLabel($record->segment) === $label
                ? 'primary'
                : 'gray')
            // Checklist behaviour on the list: once it has gone out it drops off the menu.
            // Still available on the edit page, which passes $hideSent = false.
            // Nullable: on the edit page this same action hangs off a header group, which
            // Filament evaluates with no record. $hideSent is false there anyway, but the
            // signature has to allow it or the page 500s before the group ever renders.
            ->hidden(fn (?Prospect $record) => $hideSent && $record?->hasSentEmail($label))
            ->modalHeading("Send {$label}")
            ->modalDescription($description)
            ->schema([
                Forms\Components\TagsInput::make('emails')
                    ->label('Email Address(es)')
                    ->required()
                    ->placeholder('Add email and press Enter')
                    ->default(fn (?Prospect $record) => array_values(array_filter([$record?->email])))
                    ->nestedRecursiveRules(['email']),

                Forms\Components\Textarea::make('notes')
                    ->label('Personal Note (optional)')
                    ->rows(3)
                    ->placeholder('Added to the email directly under the greeting...'),
            ])
            ->modalSubmitActionLabel("Send {$label}")
            ->action(function (Prospect $record, array $data) use ($mailable, $label) {
                ProspectMailer::send($record, new $mailable($record, $data), $data['emails'], $label);

                Notification::make()
                    ->success()
                    ->title("{$label} sent")
                    ->body('Sent to '.implode(', ', $data['emails']))
                    ->send();
            });
    }

    /**
     * Put one prospect into a campaign.
     *
     * Hidden rather than disabled once they are already walking one: a greyed-out button
     * on every row of a list is noise, and the campaign column already says where they are.
     */
    protected static function enrollAction(): Action
    {
        return Action::make('enroll')
            ->label('Campaign')
            ->icon('heroicon-o-rectangle-stack')
            ->color('primary')
            ->modalHeading('Start a campaign')
            ->modalSubmitActionLabel('Enrol')
            ->schema([
                Forms\Components\Select::make('campaign_id')
                    ->label('Campaign')
                    ->options(fn () => Campaign::active()->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->helperText('The first email goes out on the next scheduler run inside the sending window.'),

                Forms\Components\Placeholder::make('preview_warning')
                    ->label('')
                    ->content('This campaign needs a preview URL on the prospect, and this one has none. Add it first.')
                    ->visible(fn (Get $get, Prospect $record) => ($id = $get('campaign_id'))
                        && ($campaign = Campaign::find($id))
                        && CampaignRunner::needsPreview($campaign)
                        && ! $record->hasPreview()),
            ])
            ->action(function (Prospect $record, array $data) {
                $campaign = Campaign::find($data['campaign_id']);

                if (! $campaign || ! CampaignRunner::enroll($campaign, $record)) {
                    Notification::make()
                        ->warning()
                        ->title('Not enrolled')
                        ->body(static::enrollmentBlocker($campaign, $record))
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Enrolled in '.$campaign->name)
                    ->body('The first email sends on the next scheduled run.')
                    ->send();
            })
            ->hidden(fn (Prospect $record) => $record->isUnsubscribed() || $record->activeEnrollment() !== null);
    }

    /** The same thing for a selection — how a batch of newly built previews goes out. */
    protected static function enrollBulkAction(): BulkAction
    {
        return BulkAction::make('enrollMany')
            ->label('Start a campaign')
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                Forms\Components\Select::make('campaign_id')
                    ->label('Campaign')
                    ->options(fn () => Campaign::active()->orderBy('name')->pluck('name', 'id'))
                    ->required(),
            ])
            ->action(function (array $data, $records) {
                $campaign = Campaign::find($data['campaign_id']);

                if (! $campaign) {
                    return;
                }

                $enrolled = 0;
                $skipped = 0;

                foreach ($records as $record) {
                    CampaignRunner::enroll($campaign, $record) ? $enrolled++ : $skipped++;
                }

                // Skips are the interesting number here: they are almost always prospects
                // with no preview built yet, which is a to-do rather than an error.
                Notification::make()
                    ->{$enrolled > 0 ? 'success' : 'warning'}()
                    ->title($enrolled.' enrolled in '.$campaign->name)
                    ->body($skipped > 0
                        ? $skipped.' skipped — already enrolled, unsubscribed, or no preview URL yet.'
                        : null)
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /** Why a single enrolment was refused, in the words the operator needs. */
    protected static function enrollmentBlocker(?Campaign $campaign, Prospect $prospect): string
    {
        return match (true) {
            ! $campaign => 'That campaign no longer exists.',
            blank($prospect->email) => 'This prospect has no email address.',
            $prospect->isUnsubscribed() => 'This prospect has opted out.',
            CampaignRunner::needsPreview($campaign) && ! $prospect->hasPreview() =>
                'This campaign links to a preview page, and no preview URL is set on this prospect.',
            default => 'They are already in this campaign.',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'create' => Pages\CreateProspect::route('/create'),
            'edit' => Pages\EditProspect::route('/{record}/edit'),
        ];
    }
}
