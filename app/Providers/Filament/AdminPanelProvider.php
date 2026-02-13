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
                        .fi-btn-primary,
                        .fi-btn-primary:focus,
                        .fi-btn-primary:active {
                            --btn-bg: #ed2537 !important;
                            --c-400: #ed2537 !important;
                            --c-500: #ed2537 !important;
                            --c-600: #ed2537 !important;
                            background-color: #ed2537 !important;
                            color: white !important;
                        }
                        .fi-btn-primary:hover {
                            --btn-bg: #111827 !important;
                            --c-400: #111827 !important;
                            --c-500: #111827 !important;
                            --c-600: #111827 !important;
                            background-color: #111827 !important;
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
