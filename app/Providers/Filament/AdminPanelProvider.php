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
                fn () => new \Illuminate\Support\HtmlString('
                    <style>
                        /* ── Sidebar Vercel-like ── */
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

                        /* ══════════════════════════════════════════
                           ANTIGRAVITY LOGIN — Dark + Glassmorphism
                           ══════════════════════════════════════════ */

                        /* 1) Full-page dark canvas */
                        .fi-simple-layout {
                            background-color: #08090c !important;
                            position: relative;
                            overflow: hidden;
                        }

                        /* 2) Subtle grid pattern overlay */
                        .fi-simple-layout::after {
                            content: "";
                            position: absolute;
                            inset: 0;
                            z-index: 0;
                            background-image:
                                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
                            background-size: 60px 60px;
                            pointer-events: none;
                        }

                        /* 3) Glowing orange gradient — follows mouse */
                        .fi-simple-layout::before {
                            content: "";
                            position: absolute;
                            inset: 0;
                            z-index: 0;
                            background:
                                radial-gradient(
                                    700px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                                    rgba(249, 115, 22, 0.14),
                                    transparent 45%
                                );
                            pointer-events: none;
                            transition: background 0.15s ease-out;
                        }

                        /* 4) Card wrapper – lift above pseudo-elements */
                        .fi-simple-main-ctn {
                            z-index: 1;
                            position: relative;
                        }

                        /* 5) Glassmorphism card (the <main> itself has bg-white) */
                        .fi-simple-main {
                            background: rgba(17, 19, 24, 0.65) !important;
                            backdrop-filter: blur(20px) saturate(1.4) !important;
                            -webkit-backdrop-filter: blur(20px) saturate(1.4) !important;
                            border: 1px solid rgba(255, 255, 255, 0.06) !important;
                            box-shadow:
                                0 0 0 1px rgba(249, 115, 22, 0.05),
                                0 0 60px -15px rgba(249, 115, 22, 0.10),
                                0 25px 60px -15px rgba(0, 0, 0, 0.55) !important;
                            ring: none !important;
                            --tw-ring-shadow: none !important;
                            transition: box-shadow 0.4s ease, border-color 0.4s ease;
                        }
                        .fi-simple-main:hover {
                            border-color: rgba(249, 115, 22, 0.15) !important;
                            box-shadow:
                                0 0 0 1px rgba(249, 115, 22, 0.08),
                                0 0 80px -10px rgba(249, 115, 22, 0.14),
                                0 30px 70px -15px rgba(0, 0, 0, 0.60) !important;
                        }

                        /* 6) Typography — force light text on dark card */
                        .fi-simple-layout .fi-simple-header-heading,
                        .fi-simple-layout .fi-header-heading {
                            color: #f1f1f4 !important;
                        }
                        .fi-simple-layout .fi-simple-header-subheading {
                            color: #8b8fa3 !important;
                        }

                        /* 7) Form labels & helper text */
                        .fi-simple-layout .fi-fo-field-wrp label,
                        .fi-simple-layout .fi-input-wrp label {
                            color: #c5c8d6 !important;
                        }

                        /* 8) Input fields — dark glass look */
                        .fi-simple-layout .fi-input-wrp {
                            background: rgba(255, 255, 255, 0.04) !important;
                            border-color: rgba(255, 255, 255, 0.08) !important;
                            transition: border-color 0.2s ease, box-shadow 0.2s ease;
                        }
                        .fi-simple-layout .fi-input-wrp:focus-within {
                            border-color: rgba(249, 115, 22, 0.45) !important;
                            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10) !important;
                        }
                        .fi-simple-layout .fi-input {
                            color: #e8e9f0 !important;
                            background: transparent !important;
                        }
                        .fi-simple-layout .fi-input::placeholder {
                            color: #555770 !important;
                        }

                        /* 9) Checkbox & remember me */
                        .fi-simple-layout .fi-checkbox-input {
                            background: rgba(255, 255, 255, 0.05) !important;
                            border-color: rgba(255, 255, 255, 0.12) !important;
                        }
                        .fi-simple-layout .fi-checkbox-label,
                        .fi-simple-layout .fi-fo-field-wrp .fi-checkbox-label {
                            color: #9195ab !important;
                        }

                        /* 10) Submit button — bright orange glow */
                        .fi-simple-layout .fi-btn-primary {
                            background: linear-gradient(135deg, #f97316, #ea580c) !important;
                            box-shadow: 0 4px 20px -4px rgba(249, 115, 22, 0.45) !important;
                            border: none !important;
                            transition: transform 0.15s ease, box-shadow 0.25s ease;
                        }
                        .fi-simple-layout .fi-btn-primary:hover {
                            transform: translateY(-1px);
                            box-shadow: 0 6px 28px -4px rgba(249, 115, 22, 0.55) !important;
                        }
                        .fi-simple-layout .fi-btn-primary:active {
                            transform: translateY(0);
                        }

                        /* 11) Brand logo — add subtle glow */
                        .fi-simple-layout .fi-logo img,
                        .fi-simple-layout .fi-simple-header img {
                            filter: drop-shadow(0 0 12px rgba(249, 115, 22, 0.3));
                        }

                        /* 12) Eye/reveal icon on password */
                        .fi-simple-layout .fi-input-wrp button {
                            color: #6b6f85 !important;
                        }
                        .fi-simple-layout .fi-input-wrp button:hover {
                            color: #f97316 !important;
                        }

                        /* 13) Fade-in animation */
                        @keyframes antigravity-fade-in {
                            from { opacity: 0; transform: translateY(16px) scale(0.98); }
                            to   { opacity: 1; transform: translateY(0)   scale(1); }
                        }
                        .fi-simple-main {
                            animation: antigravity-fade-in 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
                        }
                    </style>
                    <script>
                        // Interactive mouse-tracking glow effect
                        document.addEventListener("mousemove", (e) => {
                            document.documentElement.style.setProperty("--mouse-x", e.clientX + "px");
                            document.documentElement.style.setProperty("--mouse-y", e.clientY + "px");
                        });
                    </script>
                ')
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
