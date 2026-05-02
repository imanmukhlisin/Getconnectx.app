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
            ->passwordReset()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->authGuard('admin')
            ->brandLogo('/images/logo.png')
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/logo.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->renderHook(
                'panels::head.done',
                fn () => new \Illuminate\Support\HtmlString("
                    <style>
                        /* Sidebar Vercel-like */
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
                        ::-webkit-scrollbar { width: 5px; }
                        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
                        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
                        
                        /* Antigravity Hover Background untuk Login Page */
                        .fi-simple-layout {
                            background-color: #0b0d10 !important;
                            position: relative;
                            overflow: hidden;
                        }
                        .fi-simple-layout::before {
                            content: '';
                            position: absolute;
                            top: 0; left: 0; right: 0; bottom: 0;
                            z-index: 0;
                            background: radial-gradient(800px circle at var(--mouse-x, 50vw) var(--mouse-y, 50vh), rgba(249, 115, 22, 0.15), transparent 40%);
                            pointer-events: none;
                            transition: background 0.1s ease;
                        }
                        /* Bikin form login agak transparan kayak kaca (Glassmorphism) */
                        .fi-simple-main {
                            z-index: 1;
                            position: relative;
                        }
                        .fi-simple-main > div {
                            background: rgba(30, 32, 38, 0.6) !important;
                            backdrop-filter: blur(12px) !important;
                            -webkit-backdrop-filter: blur(12px) !important;
                            border: 1px solid rgba(255, 255, 255, 0.05) !important;
                            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
                        }
                    </style>
                    <script>
                        // JS buat ngikutin mouse hover
                        document.addEventListener('mousemove', (e) => {
                            document.documentElement.style.setProperty('--mouse-x', e.clientX + 'px');
                            document.documentElement.style.setProperty('--mouse-y', e.clientY + 'px');
                        });
                    </script>
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
