<?php

namespace App\Providers;

use App\Filament\Resources\RfpScreenResource\Pages\ListRfpScreens;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Inject the date range filter into the Screenah list page header,
        // right before the "Screen RFP" action button.
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
            fn (): string => Blade::render('@include("filament.rfp-list-date-range")'),
            scopes: [ListRfpScreens::class],
        );
    }
}
