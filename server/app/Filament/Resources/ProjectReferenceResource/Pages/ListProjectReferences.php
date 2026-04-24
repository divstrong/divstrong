<?php

namespace App\Filament\Resources\ProjectReferenceResource\Pages;

use App\Filament\Resources\ProjectReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectReferences extends ListRecords
{
    protected static string $resource = ProjectReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
