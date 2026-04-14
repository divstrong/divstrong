<?php

namespace App\Filament\Resources\TermsLibraryResource\Pages;

use App\Filament\Resources\TermsLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTermsLibraryItem extends EditRecord
{
    protected static string $resource = TermsLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
