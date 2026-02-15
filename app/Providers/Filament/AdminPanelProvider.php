<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use App\Filament\Widgets\ProposalStatsWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandLogo(asset('images/logo.png'))
            ->darkModeBrandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::hex('#ed2537'),
                'gray' => Color::Zinc,
            ])
            ->darkMode()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <style>
                        .fi-simple-layout {
                            background-image: url("{{ asset("images/loginbg.png") }}");
                            background-size: cover;
                            background-position: center;
                            background-repeat: no-repeat;
                        }
                        .fi-simple-layout .fi-simple-main-ctn {
                            background-color: rgba(0, 0, 0, 0.5);
                            backdrop-filter: blur(2px);
                        }
                        .fi-simple-main {
                            background-color: white;
                            border-radius: 1rem;
                            padding: 2rem;
                            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                        }
                        .fi-btn.fi-color-primary:not(.fi-outlined) {
                            --bg: #ed2537 !important;
                            --hover-bg: #c91e2e !important;
                            --text: white !important;
                            --hover-text: #FFD700 !important;
                            --dark-bg: #ed2537 !important;
                            --dark-hover-bg: #c91e2e !important;
                            --dark-text: white !important;
                            --dark-hover-text: #FFD700 !important;
                            background: linear-gradient(to bottom, #f43f4f 0%, #ed2537 40%, #c91e2e 100%) !important;
                            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 2px 8px rgba(237,37,55,0.3) !important;
                            border: 1px solid #b91a27 !important;
                            color: white !important;
                            transition: transform 0.3s ease, box-shadow 0.3s ease, color 0.3s ease !important;
                        }
                        .fi-btn.fi-color-primary:not(.fi-outlined):hover {
                            background: linear-gradient(to bottom, #f43f4f 0%, #ed2537 40%, #c91e2e 100%) !important;
                            color: #FFD700 !important;
                            transform: scale(1.06);
                            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 6px 20px rgba(237,37,55,0.4) !important;
                        }
                    </style>
                '),
            )
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ProposalStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
