<?php

namespace App\Filament\Resources\BugReporterSiteResource\Pages;

use App\Filament\Resources\BugReporterSiteResource;
use App\Models\BugReporterSite;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBugReporterSite extends EditRecord
{
    protected static string $resource = BugReporterSiteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['domain'] = BugReporterSite::normalizeDomain($data['domain'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
