<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostingAccountResource\Pages;
use App\Filament\Resources\HostingAccountResource\RelationManagers\InvoicesRelationManager;
use App\Models\Client;
use App\Models\HostingAccount;
use App\Models\HostingInvoice;
use App\Support\HostingBilling;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hosted sites, the term each is paid up to, and its renewal invoices.
 *
 * The renewal goes out as a PayPal invoice with our own branded notice (see HostingBilling);
 * paying it rolls the term forward a year. Reminders are a button for now.
 */
class HostingAccountResource extends Resource
{
    protected static ?string $model = HostingAccount::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('hosting') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Hosting';

    protected static ?string $modelLabel = 'Hosting Account';

    protected static ?int $navigationSort = 3;

    /** Renewals due within the invoicing window that have not been invoiced yet. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::dueForInvoiceQuery()->get()->filter(fn (HostingAccount $a) => ! $a->currentRenewal())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Renewals due within ' . config('hosting.invoice_days_before') . ' days, not yet invoiced';
    }

    public static function dueForInvoiceQuery(): Builder
    {
        return HostingAccount::query()->active()
            ->with(['client', 'renewalInvoice'])
            ->whereDate('term_end', '<=', now()->addDays((int) config('hosting.invoice_days_before')));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->relationship('client', 'company')
                        ->getOptionLabelFromRecordUsing(fn (Client $record) => $record->company ?: $record->name)
                        ->searchable(['company', 'name', 'email'])
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            $client = $state ? Client::find($state) : null;

                            if ($client && blank($get('name'))) {
                                $set('name', $client->company ?: $client->name);
                            }
                        }),

                    Forms\Components\TextInput::make('name')
                        ->label('Account name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('How it appears in the list and on the invoice.'),

                    Forms\Components\TextInput::make('domain')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('example.com')
                        ->dehydrateStateUsing(fn (?string $state) => $state === null ? null
                            : preg_replace('#^https?://#i', '', rtrim(trim($state), '/'))),

                    Forms\Components\TextInput::make('billing_email')
                        ->label('Billing email')
                        ->email()
                        ->maxLength(255)
                        ->helperText('Where renewal invoices go. Leave blank to use the client\'s email.'),

                    Forms\Components\Select::make('status')
                        ->options(HostingAccount::statuses())
                        ->default(HostingAccount::STATUS_ACTIVE)
                        ->required(),

                    Forms\Components\Toggle::make('auto_renew')
                        ->label('Invoice renewals automatically')
                        ->default(true)
                        ->helperText(config('hosting.auto_invoice')
                            ? 'The renewal invoice goes out ' . config('hosting.invoice_days_before') . ' days before the term ends.'
                            : 'Automatic invoicing is switched off site-wide (HOSTING_AUTO_INVOICE) — use the Send renewal invoice button.'),
                ])
                ->columns(2),

            Section::make('Term & rate')
                ->schema([
                    Forms\Components\DatePicker::make('term_start')
                        ->required()
                        ->native(false)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if ($state && blank($get('term_end'))) {
                                $set('term_end', \Illuminate\Support\Carbon::parse($state)->addYearNoOverflow()->subDay()->toDateString());
                            }
                        }),

                    Forms\Components\DatePicker::make('term_end')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('term_start')
                        ->helperText('The renewal is due on this date.'),

                    Forms\Components\TextInput::make('monthly_rate')
                        ->label('Monthly rate')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Set $set) => $set('term_amount', number_format((float) $state * 12, 2, '.', ''))),

                    Forms\Components\TextInput::make('term_amount')
                        ->label('Amount per term')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->helperText('What each yearly renewal invoice charges. Fills in as monthly × 12; override if agreed otherwise.'),

                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['client', 'renewalInvoice']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Account')
                    ->weight('semibold')
                    ->description(fn (HostingAccount $record) => $record->client && $record->client->company !== $record->name
                        ? $record->client->company ?: $record->client->name
                        : null)
                    ->searchable(['name', 'domain', 'billing_email'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain')
                    ->url(fn (HostingAccount $record) => 'https://' . $record->domain)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->description(fn (HostingAccount $record) => $record->billingEmails()[0] ?? 'No billing email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('term_start')
                    ->label('Term start')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('term_end')
                    ->label('Term end')
                    ->date('M j, Y')
                    ->sortable()
                    ->description(fn (HostingAccount $record) => match (true) {
                        $record->status !== HostingAccount::STATUS_ACTIVE => null,
                        $record->daysUntilDue() < 0 => abs($record->daysUntilDue()) . ' days past',
                        $record->daysUntilDue() === 0 => 'today',
                        default => 'in ' . $record->daysUntilDue() . ' days',
                    })
                    ->color(fn (HostingAccount $record) => match (true) {
                        $record->status !== HostingAccount::STATUS_ACTIVE => 'gray',
                        $record->daysUntilDue() < 0 => 'danger',
                        $record->daysUntilDue() <= (int) config('hosting.invoice_days_before') => 'warning',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('monthly_rate')
                    ->label('Rate')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('term_amount')
                    ->label('Total due')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('renewal')
                    ->label('Renewal')
                    ->badge()
                    ->state(fn (HostingAccount $record) => static::renewalState($record))
                    ->color(fn (string $state) => match ($state) {
                        'Paid' => 'success',
                        'Invoiced' => 'info',
                        'Overdue' => 'danger',
                        'Canceled' => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(fn (HostingAccount $record) => ($invoice = $record->currentRenewal())
                        ? '#' . $invoice->invoice_number . ' · sent ' . $invoice->sent_at?->format('M j')
                            . ($invoice->reminder_count ? ' · ' . $invoice->reminder_count . ' reminder(s), last ' . $invoice->last_reminded_at?->format('M j') : '')
                            . ($invoice->paid_at ? ' · paid ' . $invoice->paid_at->format('M j') : '')
                        : null),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => HostingAccount::statuses()[$state] ?? $state)
                    ->color(fn (string $state) => $state === HostingAccount::STATUS_ACTIVE ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('term_end')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(HostingAccount::statuses())
                    ->default(HostingAccount::STATUS_ACTIVE),

                Tables\Filters\Filter::make('due_soon')
                    ->label('Due within ' . config('hosting.invoice_days_before') . ' days')
                    ->query(fn (Builder $query) => $query->whereDate('term_end', '<=', now()->addDays((int) config('hosting.invoice_days_before')))),
            ])
            ->actions([
                static::sendInvoiceAction(),
                static::remindAction(),

                ActionGroup::make([
                    Action::make('openInvoice')
                        ->label('Open PayPal invoice')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (HostingAccount $record) => $record->currentRenewal()?->payment_url)
                        ->openUrlInNewTab()
                        ->visible(fn (HostingAccount $record) => filled($record->currentRenewal()?->payment_url)),

                    Action::make('checkPayment')
                        ->label('Check payment')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (HostingAccount $record) => $record->currentRenewal()?->isOpen() ?? false)
                        ->action(function (HostingAccount $record) {
                            $paid = static::attempt(fn () => app(HostingBilling::class)->sync($record->currentRenewal()));

                            if ($paid !== null) {
                                Notification::make()
                                    ->{$paid ? 'success' : 'info'}()
                                    ->title($paid ? 'Paid — term extended to ' . $record->fresh()->term_end->format('M j, Y') : 'Not paid yet')
                                    ->send();
                            }
                        }),

                    Action::make('markPaid')
                        ->label('Mark paid (outside PayPal)')
                        ->icon('heroicon-o-banknotes')
                        ->requiresConfirmation()
                        ->modalDescription('For a renewal paid by check or transfer. Extends the term a year. The PayPal invoice is canceled so it cannot be paid twice.')
                        ->visible(fn (HostingAccount $record) => $record->currentRenewal()?->isOpen() ?? false)
                        ->action(function (HostingAccount $record) {
                            $invoice = $record->currentRenewal();
                            $billing = app(HostingBilling::class);

                            static::attempt(function () use ($invoice, $billing) {
                                $billing->cancel($invoice);
                                $billing->markPaid($invoice->fresh());
                            });

                            Notification::make()->success()->title('Marked paid — term extended')->send();
                        }),

                    EditAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('checkAllPayments')
                    ->label('Check payments')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function () {
                        $billing = app(HostingBilling::class);
                        $open = HostingInvoice::where('status', HostingInvoice::STATUS_SENT)->with('account')->get();
                        $paid = $open->filter(fn (HostingInvoice $invoice) => static::attempt(fn () => $billing->sync($invoice), notify: false))->count();

                        Notification::make()->success()
                            ->title($open->count() . ' open invoice(s) checked')
                            ->body($paid . ' newly paid.')
                            ->send();
                    }),
            ]);
    }

    public static function sendInvoiceAction(): Action
    {
        return Action::make('sendInvoice')
            ->label('Send renewal invoice')
            ->icon('heroicon-o-document-currency-dollar')
            ->color('primary')
            ->visible(fn (HostingAccount $record) => $record->status === HostingAccount::STATUS_ACTIVE
                && (! ($invoice = $record->currentRenewal()) || $invoice->isCanceled()))
            ->modalHeading(fn (HostingAccount $record) => 'Send renewal invoice · ' . $record->domain)
            ->modalDescription(fn (HostingAccount $record) => 'Creates a PayPal invoice for $' . number_format((float) $record->term_amount, 2)
                . ' covering ' . $record->nextTermStart()->format('M j, Y') . ' – ' . $record->nextTermEnd()->format('M j, Y')
                . ', due ' . $record->term_end->copy()->max(now()->startOfDay())->format('M j, Y')
                . ', and emails the branded renewal notice with the payment link.')
            ->schema([
                Forms\Components\TagsInput::make('emails')
                    ->label('Send to')
                    ->required()
                    ->placeholder('Add email and press Enter')
                    ->default(fn (HostingAccount $record) => $record->billingEmails())
                    ->nestedRecursiveRules(['email']),
            ])
            ->modalSubmitActionLabel('Create & send invoice')
            ->action(function (HostingAccount $record, array $data) {
                $invoice = static::attempt(fn () => app(HostingBilling::class)->raiseRenewal($record, $data['emails']));

                if ($invoice) {
                    Notification::make()->success()
                        ->title('Invoice #' . $invoice->invoice_number . ' sent')
                        ->body('Emailed to ' . implode(', ', $data['emails']))
                        ->send();
                }
            });
    }

    public static function remindAction(): Action
    {
        return Action::make('remind')
            ->label('Send reminder')
            ->icon('heroicon-o-bell-alert')
            ->color('warning')
            ->visible(fn (HostingAccount $record) => $record->currentRenewal()?->isOpen() ?? false)
            ->modalHeading(fn (HostingAccount $record) => 'Payment reminder · ' . $record->domain)
            ->modalDescription(fn (HostingAccount $record) => ($invoice = $record->currentRenewal())
                ? 'Invoice #' . $invoice->invoice_number . ' for $' . number_format((float) $invoice->amount, 2)
                    . ', due ' . $invoice->due_date->format('M j, Y') . '. Checks PayPal first, so a paid invoice is never chased.'
                    . ($invoice->reminder_count ? ' Already reminded ' . $invoice->reminder_count . ' time(s), last on ' . $invoice->last_reminded_at->format('M j') . '.' : '')
                : null)
            ->schema([
                Forms\Components\TagsInput::make('emails')
                    ->label('Send to')
                    ->required()
                    ->placeholder('Add email and press Enter')
                    ->default(fn (HostingAccount $record) => $record->currentRenewal()?->recipients ?: $record->billingEmails())
                    ->nestedRecursiveRules(['email']),
            ])
            ->modalSubmitActionLabel('Send reminder')
            ->action(function (HostingAccount $record, array $data) {
                $sent = static::attempt(function () use ($record, $data) {
                    app(HostingBilling::class)->remind($record->currentRenewal(), $data['emails']);

                    return true;
                });

                if ($sent) {
                    Notification::make()->success()->title('Reminder sent')->body('To ' . implode(', ', $data['emails']))->send();
                }
            });
    }

    public static function renewalState(HostingAccount $record): string
    {
        $invoice = $record->currentRenewal();

        return match (true) {
            $invoice === null => 'Not invoiced',
            $invoice->isPaid() => 'Paid',
            $invoice->isCanceled() => 'Canceled',
            $invoice->isOverdue() => 'Overdue',
            default => 'Invoiced',
        };
    }

    /**
     * Run a billing call, turning a refusal or a PayPal error into a notification rather
     * than a 500. Returns null when it failed.
     */
    public static function attempt(\Closure $call, bool $notify = true): mixed
    {
        try {
            return $call();
        } catch (\App\Exceptions\PayPalException $e) {
            report($e);
            $message = $e->issue() === 'NOT_AUTHORIZED'
                ? 'The PayPal app is not allowed to create invoices. In developer.paypal.com → Apps & Credentials, open the app and switch on the Invoicing feature, then run "php artisan paypal:check".'
                : 'PayPal said: ' . ($e->issue() ?? $e->getMessage()) . ($e->debugId() ? ' (ref ' . $e->debugId() . ')' : '');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
        }

        if ($notify) {
            Notification::make()->warning()->title('Not done')->body($message)->persistent()->send();
        }

        return null;
    }

    public static function getRelations(): array
    {
        return [InvoicesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostingAccounts::route('/'),
            'create' => Pages\CreateHostingAccount::route('/create'),
            'edit' => Pages\EditHostingAccount::route('/{record}/edit'),
        ];
    }
}
