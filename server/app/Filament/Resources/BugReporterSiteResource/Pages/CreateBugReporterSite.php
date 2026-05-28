<?php

namespace App\Filament\Resources\BugReporterSiteResource\Pages;

use App\Filament\Resources\BugReporterSiteResource;
use App\Models\BugReporterSite;
use Filament\Resources\Pages\CreateRecord;

class CreateBugReporterSite extends CreateRecord
{
    protected static string $resource = BugReporterSiteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['domain'] = BugReporterSite::normalizeDomain($data['domain'] ?? null);

        return $data;
    }
}
