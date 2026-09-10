<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProspect extends CreateRecord
{
    protected static string $resource = ProspectResource::class;

    /**
     * Straight to the record after creating it.
     *
     * A hand-added prospect is one somebody just met, and the next thing they do is either
     * write a note about the conversation or send the intro — both of which live on the
     * edit page. Landing back on the list means finding the row again first.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
