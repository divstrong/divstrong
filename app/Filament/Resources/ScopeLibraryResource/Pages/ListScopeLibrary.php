<?php

namespace App\Filament\Resources\ScopeLibraryResource\Pages;

use App\Filament\Resources\ScopeLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScopeLibrary extends ListRecords
{
    protected static string $resource = ScopeLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
