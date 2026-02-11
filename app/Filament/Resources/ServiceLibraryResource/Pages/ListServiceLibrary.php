<?php

namespace App\Filament\Resources\ServiceLibraryResource\Pages;

use App\Filament\Resources\ServiceLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceLibrary extends ListRecords
{
    protected static string $resource = ServiceLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
