<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UserRegistrationChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->authGuard('admin')
            ->brandLogo('/images/logo.png')
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo.png'))
            ->sidebarCollapsible()
            ->globalSearchKeyBinding('command+k')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->renderHook(
                'panels::head.done',
                fn () => new \Illuminate\Support\HtmlString("
                    <style>
                        .fi-sidebar-item-button {
                            padding-top: 0.4rem !important;
                            padding-bottom: 0.4rem !important;
                            margin-top: 0.1rem !important;
                            margin-bottom: 0.1rem !important;
                        }
                        .fi-sidebar-group-label {
                            font-size: 0.7rem !important;
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            padding-bottom: 0.5rem !important;
                        }
                        .fi-sidebar-item-label {
                            font-size: 0.875rem !important;
                            font-weight: 500 !important;
                        }
                        .fi-sidebar-nav {
                            gap: 0.25rem !important;
                        }
                        /* Scrollbar minimalis ala Vercel */
                        ::-webkit-scrollbar {
                            width: 5px;
                        }
                        ::-webkit-scrollbar-thumb {
                            background: rgba(255, 255, 255, 0.1);
                            border-radius: 10px;
                        }
                        ::-webkit-scrollbar-thumb:hover {
                            background: rgba(255, 255, 255, 0.2);
                        }
                    </style>
                ")
            )
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                StatsOverviewWidget::class,
                UserRegistrationChart::class,
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
