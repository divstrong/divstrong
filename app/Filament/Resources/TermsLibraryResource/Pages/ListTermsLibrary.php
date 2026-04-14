<?php

namespace App\Filament\Resources\TermsLibraryResource\Pages;

use App\Filament\Resources\TermsLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTermsLibrary extends ListRecords
{
    protected static string $resource = TermsLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
