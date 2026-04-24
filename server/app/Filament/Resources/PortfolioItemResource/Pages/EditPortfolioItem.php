<?php

namespace App\Filament\Resources\PortfolioItemResource\Pages;

use App\Filament\Resources\PortfolioItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortfolioItem extends EditRecord
{
    protected static string $resource = PortfolioItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
