<?php

namespace App\Filament\Resources\ScopeLibraryResource\Pages;

use App\Filament\Resources\ScopeLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScopeLibraryItem extends EditRecord
{
    protected static string $resource = ScopeLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
