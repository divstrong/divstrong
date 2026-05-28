<?php

namespace App\Filament\Resources\BugReporterSiteResource\Pages;

use App\Filament\Resources\BugReporterSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBugReporterSites extends ListRecords
{
    protected static string $resource = BugReporterSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
