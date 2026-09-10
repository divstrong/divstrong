<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use App\Filament\Widgets\ProspectEmailActivityChart;
use App\Filament\Widgets\ProspectStatsWidget;
use App\Livewire\ProspectDiscoveryProgress;
use App\Models\Prospect;
use App\Models\ProspectDiscoveryRun;
use App\Support\Prospecting\ContactDiscovery;
use App\Support\Prospecting\DiscoveryRunner;
use App\Support\Prospecting\PlacesDiscovery;
use App\Support\Prospecting\ProspectingException;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    /**
     * Values that mean "we didn't find one" in a hand-built research sheet. Left alone they
     * get stored as if they were real contact names and email addresses.
     */
    protected const PLACEHOLDER_VALUES = [
        'n/a', 'na', 'none', 'unknown', 'not found', 'not found publicly', 'not public',
        'tbd', 'unlisted', '-', '--',
    ];

    /**
     * Fields that make a row a prospect. A sheet that maps none of them is not prospect data
     * — it is the "Notes"/"Legend"/"Key" tab that hand-built research workbooks ship
     * alongside the real one, and every row of it would otherwise be counted as a skip.
     */
    protected const IDENTITY_FIELDS = ['name', 'first', 'last', 'company', 'email'];

    /**
     * The discovery banner goes above the table, not in a render hook.
     *
     * It has to be a real Livewire component in the page's own tree, because it is not a
     * status display — it is what drives the run forward, one bounded step per poll. A
     * render hook would give the same pixels and no polling target.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            Livewire::make(ProspectDiscoveryProgress::class),
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->findProspectsAction(),

            Actions\Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading('Import Prospects')
                ->modalDescription('Upload a CSV or Excel file (.csv, .xls, .xlsx, .xlsm). The first row should contain column headers. Common headers (name, first, last, company, email, phone, address, city, state, zip, website) are auto-mapped.')
                ->schema([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV or Excel File')
                        ->required()
                        ->disk('local')
                        ->directory('prospect-imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            // .xlsm — research workbooks come out of Excel macro-enabled often
                            // enough, and PhpSpreadsheet reads them with the same Xlsx reader.
                            'application/vnd.ms-excel.sheet.macroEnabled.12',
                        ])
                        ->storeFileNamesIn('file_original_name'),

                    Forms\Components\Select::make('segment')
                        ->label('Segment')
                        ->options(fn () => Prospect::segments())
                        ->default(Prospect::SEGMENT_AGENCY)
                        ->required()
                        ->helperText('Applied to every row in the file. It decides which email each row offers first.'),

                    Forms\Components\TextInput::make('source')
                        ->label('Source Tag')
                        ->helperText('Applied to every prospect imported (e.g. "AIGA list"). Leave blank for no tag.')
                        ->maxLength(255)
                        ->datalist(fn () => ProspectResource::sourceOptions()),

                    Forms\Components\Select::make('assigned_to')
                        ->label('Assign To')
                        ->options(fn () => ProspectResource::panelUserOptions())
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id())
                        ->helperText('Every prospect in this file is assigned to this owner. Leave blank to import them unassigned.'),

                    Forms\Components\Toggle::make('skip_duplicates')
                        ->label('Skip duplicates (by email)')
                        ->default(true),
                ])
                ->modalSubmitActionLabel('Import')
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $source = trim($data['source'] ?? '') ?: null;
                    $segment = $data['segment'] ?? Prospect::SEGMENT_AGENCY;
                    $skipDuplicates = (bool) ($data['skip_duplicates'] ?? true);
                    $assignedTo = ($data['assigned_to'] ?? null) ?: null;

                    try {
                        $result = $this->processImport($path, $source, $segment, $skipDuplicates, $assignedTo);
                    } catch (\Throwable $e) {
                        Log::error('Prospect import failed', [
                            'error' => $e->getMessage(),
                            'file' => $data['file'] ?? null,
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    // A green "Import Complete" over a wall of failed rows is how a total
                    // failure gets mistaken for a success. Rank the notification by what
                    // actually landed, and say why the rows died.
                    $summary = "Imported: {$result['imported']} | Skipped: {$result['skipped']} | Failed: {$result['failed']}";

                    if ($assignedTo && $result['imported'] > 0) {
                        $summary .= ' — assigned to '.(\App\Models\User::find($assignedTo)?->name ?? 'unknown user');
                    }

                    if ($result['failed'] > 0) {
                        Notification::make()
                            ->{$result['imported'] > 0 ? 'warning' : 'danger'}()
                            ->title($result['imported'] > 0 ? 'Imported With Errors' : 'Nothing Imported')
                            ->body($summary.($result['firstError'] ? ' — first error: '.$result['firstError'] : ''))
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Import Complete')
                        ->body($summary)
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('New Prospect'),
        ];
    }

    /**
     * "Find Prospects" — go and find creative teams instead of waiting for a spreadsheet.
     *
     * Deliberately shaped like Import (owner, source tag, segment) because it lands in the
     * same place; the difference is only where the rows come from.
     *
     * The work itself is done by ProspectDiscoveryProgress polling above the table. This
     * action opens the run and hands it over — researching fifty agencies here would hold
     * one HTTP request open for several minutes and time out.
     */
    protected function findProspectsAction(): Actions\Action
    {
        return Actions\Action::make('findProspects')
            ->label('Find Prospects')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->modalHeading('Find New Prospects')
            ->modalDescription('Searches for creative and media teams — agencies, SEO firms, design studios, video and media houses — reads the contact details off their own sites, and adds the ones with a verified, named address.')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Forms\Components\Radio::make('company_source')
                    ->label('Where to look')
                    ->options(fn () => DiscoveryRunner::sourceOptions())
                    ->descriptions(fn () => DiscoveryRunner::sourceDescriptions())
                    ->default(fn () => PlacesDiscovery::fromConfig()->isConfigured()
                        ? DiscoveryRunner::VIA_PLACES
                        : DiscoveryRunner::VIA_MODEL)
                    ->required(),

                Forms\Components\TextInput::make('target_count')
                    ->label('How many prospects')
                    ->numeric()
                    ->default(50)
                    ->minValue(1)
                    ->maxValue(200)
                    ->required()
                    ->helperText('Stops once this many verified contacts are found, or when the search is exhausted.'),

                Forms\Components\TextInput::make('source')
                    ->label('Source Tag')
                    ->default(fn () => 'Discovery '.now()->format('M j'))
                    ->maxLength(255)
                    ->helperText('Applied to everything found, so this batch stays identifiable later.')
                    ->datalist(fn () => ProspectResource::sourceOptions()),

                Forms\Components\Select::make('assigned_to')
                    ->label('Assign To')
                    ->options(fn () => ProspectResource::panelUserOptions())
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id())
                    ->helperText('Everything found is assigned to this owner.'),

                Forms\Components\Select::make('region')
                    ->label('Region')
                    ->placeholder('Rotate across the country')
                    ->options(fn () => collect(ContactDiscovery::REGIONS)
                        ->mapWithKeys(fn (string $r) => [$r => Str::ucfirst($r)])
                        ->all())
                    ->helperText('Leave blank to spread the search around — each round covers a different area.'),

                Forms\Components\Textarea::make('notes')
                    ->label('Extra targeting (optional)')
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('e.g. agencies that serve healthcare clients, 10-30 people')
                    ->helperText('Free text, passed to the search as additional targeting.'),
            ])
            ->modalSubmitActionLabel('Start Finding')
            ->action(function (array $data) {
                $chosen = $data['company_source'] ?? DiscoveryRunner::VIA_AUTO;
                $discovery = DiscoveryRunner::source($chosen);

                if (! $discovery->isConfigured()) {
                    Notification::make()
                        ->danger()
                        ->title('That source is not set up')
                        ->body($discovery instanceof PlacesDiscovery
                            ? ProspectingException::missingPlacesKey()->getMessage()
                            : ProspectingException::missingApiKey()->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                // One at a time per user. Two runs polling in parallel would race each other
                // for the same agencies and double-import whatever both found.
                $existing = ProspectDiscoveryRun::query()
                    ->where('requested_by', auth()->id())
                    ->whereIn('status', ProspectDiscoveryRun::ACTIVE_STATUSES)
                    ->first();

                if ($existing) {
                    Notification::make()
                        ->warning()
                        ->title('A search is already running')
                        ->body('Let it finish or stop it first.')
                        ->send();

                    return;
                }

                $run = DiscoveryRunner::make()->start(
                    targetCount: (int) ($data['target_count'] ?? 50),
                    source: trim($data['source'] ?? '') ?: null,
                    assignedTo: ($data['assigned_to'] ?? null) ?: null,
                    requestedBy: auth()->id(),
                    criteria: array_filter([
                        'region' => $data['region'] ?? null,
                        'notes' => trim($data['notes'] ?? '') ?: null,
                        DiscoveryRunner::CRITERIA_KEY => $chosen,
                    ]),
                );

                // Hand off to the banner, which does the work a step per poll.
                $this->dispatch('discovery-started', runId: $run->id)
                    ->to(ProspectDiscoveryProgress::class);

                Notification::make()
                    ->info()
                    ->title('Searching '.$discovery->name().'…')
                    ->body('Progress appears above the table. You can keep working; leaving the page pauses it.')
                    ->send();
            });
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProspectStatsWidget::class,
            ProspectEmailActivityChart::class,
        ];
    }

    /**
     * Three columns so the stat tiles occupy two thirds and the activity chart stands
     * beside them in the remaining third.
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    /**
     * @return array{imported: int, skipped: int, failed: int, firstError: ?string}
     */
    protected function processImport(
        string $path,
        ?string $source,
        string $segment,
        bool $skipDuplicates,
        ?int $assignedTo = null,
    ): array {
        $spreadsheet = IOFactory::load($path);

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $sheetsProcessed = 0;
        $firstError = null;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);

            if (count($rows) < 2) {
                continue;
            }

            $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), array_shift($rows));
            $map = $this->buildColumnMap($headers);

            // A sheet needs a column that actually identifies a prospect. Matching only,
            // say, 'notes' means this is the workbook's legend tab, not its data.
            if (! array_intersect(self::IDENTITY_FIELDS, array_keys($map))) {
                Log::info('Prospect import: sheet skipped (no prospect columns)', [
                    'sheet' => $sheet->getTitle(),
                    'headers' => $headers,
                    'mapped' => array_keys($map),
                ]);

                continue;
            }

            $sheetsProcessed++;

            foreach ($rows as $row) {
                if (! $this->rowHasContent($row)) {
                    continue;
                }

                try {
                    $attrs = $this->mapRowToAttributes($row, $map);

                    if (empty($attrs['name']) && empty($attrs['company']) && empty($attrs['email'])) {
                        $skipped++;

                        continue;
                    }

                    if ($skipDuplicates && ! empty($attrs['email'])) {
                        if (Prospect::where('email', $attrs['email'])->exists()) {
                            $skipped++;

                            continue;
                        }
                    }

                    if ($source) {
                        $attrs['source'] = $source;
                    }

                    if ($assignedTo) {
                        $attrs['assigned_to'] = $assignedTo;
                    }

                    $attrs['segment'] = $segment;
                    $attrs['status'] ??= 'new';

                    Prospect::create($attrs);
                    $imported++;
                } catch (\Throwable $e) {
                    Log::warning('Prospect import row failed', [
                        'sheet' => $sheet->getTitle(),
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                    $firstError ??= $e->getMessage();
                }
            }
        }

        if ($sheetsProcessed === 0) {
            throw new \Exception('No sheets with recognizable headers were found in this file.');
        }

        return compact('imported', 'skipped', 'failed', 'firstError');
    }

    protected function normalizeHeader(string $header): string
    {
        $h = strtolower(trim($header));
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);

        return trim($h, '_');
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int> field => column index
     */
    protected function buildColumnMap(array $headers): array
    {
        $aliases = [
            'name' => ['name', 'full_name', 'contact', 'contact_name', 'primary_contact', 'owner_name'],
            'first' => ['first', 'first_name', 'firstname', 'fname'],
            'last' => ['last', 'last_name', 'lastname', 'lname', 'surname'],
            'company' => ['company', 'organization', 'business', 'business_name', 'company_name', 'agency', 'agency_name', 'studio'],
            'title' => ['title', 'role', 'job_title', 'jobtitle', 'position'],
            'email' => ['email', 'email_address', 'e_mail'],
            'phone' => ['phone', 'phone_number', 'telephone', 'tel', 'mobile', 'cell'],
            'website' => ['website', 'url', 'web', 'site', 'domain'],
            'address1' => ['address', 'address1', 'address_1', 'street', 'street_address'],
            'address2' => ['address2', 'address_2', 'suite', 'unit'],
            'city' => ['city', 'town'],
            'state' => ['state', 'st', 'province', 'region'],
            'zip' => ['zip', 'zipcode', 'zip_code', 'postal_code', 'postalcode', 'postcode'],
            'notes' => ['notes', 'note', 'comments', 'description'],
        ];

        $map = [];
        foreach ($headers as $index => $h) {
            foreach ($aliases as $field => $candidates) {
                if (in_array($h, $candidates, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        // Research sheets almost never head the contact column "name" — real examples include
        // "Owner / Principal Contact" and "Decision Maker". Fall back to the first unclaimed
        // header that reads like a person column. Exact aliases win, so a sheet with a real
        // "Name" column is unaffected.
        if (! isset($map['name'])) {
            foreach ($headers as $index => $h) {
                if (in_array($index, $map, true)) {
                    continue;
                }

                if (preg_match('/(^|_)(contact|owner|principal|founder|decision_maker)(_|$)/', $h)) {
                    $map['name'] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array<string, string>
     */
    protected function mapRowToAttributes(array $row, array $map): array
    {
        $get = function (string $key) use ($row, $map) {
            if (! isset($map[$key]) || ! isset($row[$map[$key]])) {
                return null;
            }

            $value = trim((string) $row[$map[$key]]);

            // "Not found publicly" is not a contact name.
            return in_array(strtolower($value), self::PLACEHOLDER_VALUES, true) ? null : $value;
        };

        $name = $get('name');
        if (! $name) {
            $combined = trim(($get('first') ?? '').' '.($get('last') ?? ''));
            $name = $combined !== '' ? $combined : null;
        }

        return array_filter([
            'name' => $name,
            'company' => $get('company'),
            'title' => $get('title'),
            'email' => $this->cleanEmail($get('email')),
            'phone' => $get('phone'),
            'website' => $this->cleanWebsite($get('website')),
            'address1' => $get('address1'),
            'address2' => $get('address2'),
            'city' => $get('city'),
            'state' => $this->cleanState($get('state')),
            'zip' => $this->cleanZip($get('zip')),
            'notes' => $get('notes'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param  array<int, mixed>  $row
     */
    protected function rowHasContent(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function cleanEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function cleanWebsite(?string $website): ?string
    {
        if (! $website) {
            return null;
        }

        if (! preg_match('~^https?://~i', $website)) {
            $website = 'https://'.$website;
        }

        return filter_var($website, FILTER_VALIDATE_URL) ? $website : null;
    }

    protected function cleanState(?string $state): ?string
    {
        if (! $state) {
            return null;
        }

        $state = strtoupper(trim($state));

        return strlen($state) === 2 ? $state : null;
    }

    protected function cleanZip(?string $zip): ?string
    {
        if (! $zip) {
            return null;
        }

        $zip = trim($zip);

        // Excel eats leading zeros, so an 02134 arrives as 2134.
        if (ctype_digit($zip) && strlen($zip) < 5) {
            $zip = str_pad($zip, 5, '0', STR_PAD_LEFT);
        }

        return $zip;
    }
}
