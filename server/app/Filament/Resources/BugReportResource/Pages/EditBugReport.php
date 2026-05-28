<?php

namespace App\Filament\Resources\BugReportResource\Pages;

use App\Filament\Resources\BugReportResource;
use App\Models\BugReport;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBugReport extends EditRecord
{
    protected static string $resource = BugReportResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $current = $this->record->status ?? null;
        $next = $data['status'] ?? $current;

        if ($next === 'resolved' && $current !== 'resolved') {
            $data['resolved_at'] = now();
        } elseif ($next !== 'resolved') {
            $data['resolved_at'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
