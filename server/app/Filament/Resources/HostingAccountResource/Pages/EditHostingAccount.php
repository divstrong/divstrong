<?php

namespace App\Filament\Resources\HostingAccountResource\Pages;

use App\Filament\Resources\HostingAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHostingAccount extends EditRecord
{
    protected static string $resource = HostingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            HostingAccountResource::sendInvoiceAction()->record($this->getRecord()),
            HostingAccountResource::remindAction()->record($this->getRecord()),
            Actions\DeleteAction::make()->color('gray')->icon('heroicon-o-trash'),
        ];
    }
}
