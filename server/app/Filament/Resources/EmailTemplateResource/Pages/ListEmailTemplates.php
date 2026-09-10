<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    // No create action: templates are seeded by key and looked up by key, so an arbitrary
    // new one would be a template nothing sends. See EmailTemplateResource::canCreate().
    protected function getHeaderActions(): array
    {
        return [];
    }
}
