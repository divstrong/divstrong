<?php

namespace App\Filament\Resources\ServiceLibraryResource\Pages;

use App\Filament\Resources\ServiceLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceLibraryItem extends EditRecord
{
    protected static string $resource = ServiceLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
