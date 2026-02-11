<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProposalStatus: string implements HasLabel, HasColor, HasIcon
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Converted = 'converted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Converted => 'Converted',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Viewed => 'warning',
            self::Accepted => 'success',
            self::Declined => 'danger',
            self::Converted => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Sent => 'heroicon-o-paper-airplane',
            self::Viewed => 'heroicon-o-eye',
            self::Accepted => 'heroicon-o-check-circle',
            self::Declined => 'heroicon-o-x-circle',
            self::Converted => 'heroicon-o-check-badge',
        };
    }
}
