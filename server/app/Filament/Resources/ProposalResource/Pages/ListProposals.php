<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use App\Models\Client;
use App\Models\Setting;
use App\Services\ClientProposalBuilder;
use App\Support\EngagementMix;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ListProposals extends ListRecords
{
    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->generateProposalAction(),
            Actions\CreateAction::make()
                ->label('Blank Proposal')
                ->icon('heroicon-o-document-plus')
                ->color('gray'),
        ];
    }

    /**
     * Pick a client, describe the project, size the engagement — Claude drafts
     * the overview, splits the scope into sprints, and lands an editable draft
     * with its Investment rows and payment milestones already in place.
     */
    protected function generateProposalAction(): Actions\Action
    {
        return Actions\Action::make('generateProposal')
            ->label('Create Proposal')
            ->icon('heroicon-o-sparkles')
            ->modalHeading('Create Proposal')
            ->modalDescription('Claude drafts the overview, breaks the scope into sprints written in plain language, builds the investment table, and sets a payment milestone per sprint. Everything lands as an editable draft.')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitActionLabel('Generate Proposal')
            ->form([
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->options(fn () => static::clientOptions(Client::query()->orderBy('name')->limit(50)->get()))
                    ->getSearchResultsUsing(fn (string $search) => static::clientOptions(
                        Client::query()
                            ->where(fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('company', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"))
                            ->orderBy('name')
                            ->limit(50)
                            ->get()
                    ))
                    ->getOptionLabelUsing(fn ($value) => ($client = Client::find($value))
                        ? static::clientLabel($client)
                        : null)
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
                    ->createOptionUsing(fn (array $data): int => Client::create($data)->id)
                    ->helperText('Search by name, company, or email.'),

                Forms\Components\TextInput::make('project_title')
                    ->label('Project Title')
                    ->maxLength(255)
                    ->placeholder('Leave blank and Claude will propose one')
                    ->helperText('Shown on the cover page.'),

                Forms\Components\Textarea::make('scope_prompt')
                    ->label('Project Context')
                    ->required()
                    ->rows(8)
                    ->minLength(40)
                    ->maxLength(8000)
                    ->placeholder("What are they trying to achieve, and what did they tell you? e.g. They're a regional HVAC company running a 12-year-old site they can't edit themselves. They want a rebuild on a CMS their office manager can update, service-area landing pages for the eight towns they cover, an online booking form that hands off to their scheduling software, and their 60-odd blog posts brought across. Their busy season starts in May.")
                    ->helperText('The only source material the drafter gets — the more context, the better the draft. Business goals, constraints, integrations, deadlines, what to lead with, what to leave out.')
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    Forms\Components\TextInput::make('sprints')
                        ->label('Sprints')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(EngagementMix::maxQuantity('sprint'))
                        ->default((int) (config('proposals.default_quantity.sprint') ?: 4))
                        ->live(onBlur: true)
                        ->helperText(fn () => '$' . number_format(Setting::rateFor('sprint'), 0) . ' each'),
                    Forms\Components\TextInput::make('days')
                        ->label('Days')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(EngagementMix::maxQuantity('day'))
                        ->default(0)
                        ->live(onBlur: true)
                        ->helperText(fn () => '$' . number_format(Setting::rateFor('day'), 0) . ' each'),
                    Forms\Components\TextInput::make('hours')
                        ->label('Hours')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(EngagementMix::maxQuantity('hour'))
                        ->default(0)
                        ->live(onBlur: true)
                        ->helperText(fn () => '$' . number_format(Setting::rateFor('hour'), 0) . ' each'),
                ]),

                Forms\Components\Placeholder::make('engagement_total')
                    ->label('Investment')
                    ->content(fn (callable $get) => static::engagementSummary($get))
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                $client = Client::find($data['client_id'] ?? null);

                if (! $client) {
                    Notification::make()
                        ->danger()
                        ->title('Client not found')
                        ->body('Pick a client before generating the proposal.')
                        ->send();

                    return;
                }

                $mix = EngagementMix::make(
                    $data['sprints'] ?? null,
                    $data['days'] ?? 0,
                    $data['hours'] ?? 0,
                    $data['scope_prompt'] ?? null,
                );

                try {
                    $proposal = (new ClientProposalBuilder())->build(
                        $client,
                        $mix,
                        trim((string) ($data['project_title'] ?? '')) ?: null,
                        Auth::id(),
                    );

                    Notification::make()
                        ->success()
                        ->title('Draft proposal created')
                        ->body($mix->summaryLine() . '. Add a cover image and review the generated content before sending.')
                        ->send();

                    return redirect(ProposalResource::getUrl('edit', ['record' => $proposal]));
                } catch (\Throwable $e) {
                    Log::error('Client proposal generation failed', [
                        'client_id' => $client->id,
                        'sprints' => $mix->sprints,
                        'days' => $mix->days,
                        'hours' => $mix->hours,
                        'error' => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Proposal generation failed')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    /** @param  \Illuminate\Support\Collection<int, Client>  $clients */
    public static function clientOptions($clients): array
    {
        return $clients->mapWithKeys(fn (Client $client) => [
            $client->id => static::clientLabel($client),
        ])->all();
    }

    /** "Dana Reyes — Northside HVAC" */
    public static function clientLabel(Client $client): string
    {
        return $client->company && $client->company !== $client->name
            ? "{$client->name} — {$client->company}"
            : (string) $client->name;
    }

    /** Live running total under the sizing fields, plus the sprint breakdown. */
    public static function engagementSummary(callable $get): HtmlString
    {
        $mix = EngagementMix::make($get('sprints'), $get('days'), $get('hours'));

        $lines = array_filter([
            $mix->sprintLine() . ' = $' . number_format($mix->sprintSubtotal(), 0),
            $mix->dayLine() ? $mix->dayLine() . ' = $' . number_format($mix->daySubtotal(), 0) : null,
            $mix->hourLine() ? $mix->hourLine() . ' = $' . number_format($mix->hourSubtotal(), 0) : null,
        ]);

        return new HtmlString(
            '<span style="font-size: 1.25rem; font-weight: 700;">$'
            . number_format($mix->total(), 0) . '</span>'
            . '<br><span style="color: #6b7280; font-size: 0.875rem;">'
            . implode('<br>', array_map('e', $lines))
            . '<br>Scope divided into ' . $mix->sprints . ' '
            . str('sprint')->plural($mix->sprints)
            . ' — one investment row and one milestone each.</span>'
        );
    }
}
