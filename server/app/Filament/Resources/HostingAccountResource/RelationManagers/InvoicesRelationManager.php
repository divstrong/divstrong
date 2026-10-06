<?php

namespace App\Filament\Resources\HostingAccountResource\RelationManagers;

use App\Filament\Resources\HostingAccountResource;
use App\Models\HostingInvoice;
use App\Support\HostingBilling;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Every renewal invoice raised for the account, newest first. Read-only apart from its actions. */
class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Renewal invoices';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->defaultSort('term_start', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Invoice')->prefix('#'),
                Tables\Columns\TextColumn::make('term_start')
                    ->label('Term')
                    ->formatStateUsing(fn (HostingInvoice $record) => $record->term_start->format('M j, Y') . ' – ' . $record->term_end->format('M j, Y')),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('due_date')->label('Due')->date('M j, Y'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, HostingInvoice $record) => $record->isOverdue() ? 'Overdue' : ucfirst($state))
                    ->color(fn (HostingInvoice $record) => match (true) {
                        $record->isPaid() => 'success',
                        $record->isOverdue() => 'danger',
                        $record->isOpen() => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sent_at')->label('Sent')->dateTime('M j, Y g:i A'),
                Tables\Columns\TextColumn::make('reminder_count')
                    ->label('Reminders')
                    ->formatStateUsing(fn (int $state, HostingInvoice $record) => $state
                        ? $state . ' · last ' . $record->last_reminded_at?->format('M j')
                        : '—'),
                Tables\Columns\TextColumn::make('paid_at')->label('Paid')->dateTime('M j, Y')->placeholder('—'),
            ])
            ->actions([
                Action::make('open')
                    ->label('PayPal')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (HostingInvoice $record) => $record->payment_url)
                    ->openUrlInNewTab()
                    ->visible(fn (HostingInvoice $record) => filled($record->payment_url)),

                Action::make('cancelInvoice')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Cancels the invoice in PayPal so it can no longer be paid. A new renewal invoice can then be sent.')
                    ->visible(fn (HostingInvoice $record) => $record->isOpen())
                    ->action(function (HostingInvoice $record) {
                        HostingAccountResource::attempt(fn () => app(HostingBilling::class)->cancel($record));

                        Notification::make()->success()->title('Invoice canceled')->send();
                    }),
            ]);
    }
}
