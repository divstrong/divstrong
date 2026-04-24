<?php

namespace App\Filament\Resources\ProjectReferenceResource\Pages;

use App\Filament\Resources\ProjectReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectReference extends EditRecord
{
    protected static string $resource = ProjectReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
