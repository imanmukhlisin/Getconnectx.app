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
                        /* ─── Page Loading Overlay ─── */
                        #ag-page-loader {
                            position: fixed;
                            inset: 0;
                            z-index: 99999;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            gap: 16px;
                            background: rgba(10, 10, 12, 0.55);
                            backdrop-filter: blur(12px);
                            -webkit-backdrop-filter: blur(12px);
                            transition: opacity 0.4s ease, visibility 0.4s ease;
                        }
                        #ag-page-loader.ag-loader-hidden {
                            opacity: 0;
                            visibility: hidden;
                        }
                        .ag-loader-dots {
                            width: 60px;
                            aspect-ratio: 2;
                            --_g: no-repeat radial-gradient(circle closest-side, #f97316 90%, #0000);
                            background:
                                var(--_g) 0%   50%,
                                var(--_g) 50%  50%,
                                var(--_g) 100% 50%;
                            background-size: calc(100%/3) 50%;
                            animation: ag-l3 1s infinite linear;
                        }
                        @keyframes ag-l3 {
                            20% { background-position: 0%   0%, 50%  50%, 100%  50% }
                            40% { background-position: 0% 100%, 50%   0%, 100%  50% }
                            60% { background-position: 0%  50%, 50% 100%, 100%   0% }
                            80% { background-position: 0%  50%, 50%  50%, 100% 100% }
                        }
                        .ag-loader-text {
                            font-size: 0.75rem;
                            color: rgba(249,115,22,0.7);
                            font-family: system-ui, sans-serif;
                            letter-spacing: 0.1em;
                            text-transform: uppercase;
                            font-weight: 600;
                        }
                    </style>
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

                        /* ══════════════════════════════════════════════
                           ANTIGRAVITY LOGIN — Dark + Glassmorphism + FX
                           ══════════════════════════════════════════════ */

                        /* Dark canvas */
                        .fi-simple-layout {
                            background-color: #08090c !important;
                            position: relative;
                            overflow: hidden;
                        }

                        /* ── Animated Background Elements ── */
                        .ag-bg-layer {
                            position: fixed;
                            inset: 0;
                            z-index: 0;
                            pointer-events: none;
                            overflow: hidden;
                        }

                        /* Mouse-tracking glow */
                        .ag-mouse-glow {
                            position: fixed;
                            width: 800px;
                            height: 800px;
                            border-radius: 50%;
                            background: radial-gradient(circle, rgba(249,115,22,0.18) 0%, rgba(249,115,22,0.06) 35%, transparent 70%);
                            transform: translate(-50%, -50%);
                            pointer-events: none;
                            z-index: 1;
                            transition: left 0.08s ease-out, top 0.08s ease-out;
                            will-change: left, top;
                        }

                        /* Floating orbs */
                        .ag-orb {
                            position: absolute;
                            border-radius: 50%;
                            filter: blur(80px);
                            opacity: 0;
                            will-change: transform, opacity;
                        }
                        .ag-orb-1 {
                            width: 500px; height: 500px;
                            background: rgba(249, 115, 22, 0.12);
                            top: -10%; left: -5%;
                            animation: ag-float-1 12s ease-in-out infinite;
                        }
                        .ag-orb-2 {
                            width: 400px; height: 400px;
                            background: rgba(251, 146, 60, 0.10);
                            bottom: -10%; right: -5%;
                            animation: ag-float-2 15s ease-in-out infinite 2s;
                        }
                        .ag-orb-3 {
                            width: 300px; height: 300px;
                            background: rgba(234, 88, 12, 0.08);
                            top: 50%; left: 60%;
                            animation: ag-float-3 18s ease-in-out infinite 4s;
                        }
                        .ag-orb-4 {
                            width: 250px; height: 250px;
                            background: rgba(249, 115, 22, 0.06);
                            top: 20%; right: 20%;
                            animation: ag-float-4 20s ease-in-out infinite 1s;
                        }

                        @keyframes ag-float-1 {
                            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.6; }
                            25% { transform: translate(80px, 60px) scale(1.15); opacity: 0.8; }
                            50% { transform: translate(40px, 120px) scale(0.95); opacity: 0.5; }
                            75% { transform: translate(-30px, 50px) scale(1.1); opacity: 0.7; }
                        }
                        @keyframes ag-float-2 {
                            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
                            25% { transform: translate(-70px, -50px) scale(1.1); opacity: 0.7; }
                            50% { transform: translate(-40px, -100px) scale(0.9); opacity: 0.4; }
                            75% { transform: translate(20px, -60px) scale(1.05); opacity: 0.6; }
                        }
                        @keyframes ag-float-3 {
                            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
                            33% { transform: translate(-100px, -80px) scale(1.2); opacity: 0.6; }
                            66% { transform: translate(60px, -40px) scale(0.85); opacity: 0.35; }
                        }
                        @keyframes ag-float-4 {
                            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
                            50% { transform: translate(-60px, 80px) scale(1.3); opacity: 0.55; }
                        }

                        /* Grid overlay */
                        .ag-grid {
                            position: fixed;
                            inset: 0;
                            z-index: 0;
                            pointer-events: none;
                            background-image:
                                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
                            background-size: 60px 60px;
                            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 20%, transparent 100%);
                            -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 20%, transparent 100%);
                        }

                        /* ── Card wrapper ── */
                        .fi-simple-main-ctn {
                            z-index: 2;
                            position: relative;
                        }

                        /* ── Glassmorphism Card ── */
                        .fi-simple-main {
                            background: rgba(17, 19, 24, 0.65) !important;
                            backdrop-filter: blur(24px) saturate(1.5) !important;
                            -webkit-backdrop-filter: blur(24px) saturate(1.5) !important;
                            border: 1px solid rgba(255, 255, 255, 0.06) !important;
                            box-shadow:
                                0 0 0 1px rgba(249, 115, 22, 0.05),
                                0 0 80px -20px rgba(249, 115, 22, 0.12),
                                0 25px 60px -15px rgba(0, 0, 0, 0.55) !important;
                            --tw-ring-shadow: 0 0 #0000 !important;
                            transition: box-shadow 0.4s ease, border-color 0.4s ease;
                            animation: ag-card-enter 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
                        }
                        .fi-simple-main:hover {
                            border-color: rgba(249, 115, 22, 0.18) !important;
                            box-shadow:
                                0 0 0 1px rgba(249, 115, 22, 0.10),
                                0 0 100px -10px rgba(249, 115, 22, 0.16),
                                0 30px 70px -15px rgba(0, 0, 0, 0.6) !important;
                        }
                        @keyframes ag-card-enter {
                            from { opacity: 0; transform: translateY(24px) scale(0.97); }
                            to   { opacity: 1; transform: translateY(0) scale(1); }
                        }

                        /* ── Typography ── */
                        .fi-simple-layout .fi-simple-header-heading,
                        .fi-simple-layout .fi-header-heading {
                            color: #f1f1f4 !important;
                        }
                        .fi-simple-layout .fi-simple-header-subheading {
                            color: #8b8fa3 !important;
                        }

                        /* ── Form labels ── */
                        .fi-simple-layout .fi-fo-field-wrp label,
                        .fi-simple-layout .fi-input-wrp label {
                            color: #c5c8d6 !important;
                        }

                        /* ── Input fields ── */
                        .fi-simple-layout .fi-input-wrp {
                            background: rgba(255, 255, 255, 0.04) !important;
                            border-color: rgba(255, 255, 255, 0.08) !important;
                            transition: border-color 0.2s ease, box-shadow 0.2s ease;
                        }
                        .fi-simple-layout .fi-input-wrp:focus-within {
                            border-color: rgba(249, 115, 22, 0.5) !important;
                            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12) !important;
                        }
                        .fi-simple-layout .fi-input {
                            color: #e8e9f0 !important;
                            background: transparent !important;
                        }
                        .fi-simple-layout .fi-input::placeholder {
                            color: #555770 !important;
                        }

                        /* ── Checkbox ── */
                        .fi-simple-layout .fi-checkbox-input {
                            background: rgba(255, 255, 255, 0.05) !important;
                            border-color: rgba(255, 255, 255, 0.12) !important;
                        }
                        .fi-simple-layout .fi-checkbox-label,
                        .fi-simple-layout .fi-fo-field-wrp .fi-checkbox-label {
                            color: #9195ab !important;
                        }

                        /* ── Submit button ── */
                        .fi-simple-layout .fi-btn-primary {
                            background: linear-gradient(135deg, #f97316, #ea580c) !important;
                            box-shadow: 0 4px 24px -4px rgba(249, 115, 22, 0.5) !important;
                            border: none !important;
                            transition: transform 0.15s ease, box-shadow 0.25s ease;
                        }
                        .fi-simple-layout .fi-btn-primary:hover {
                            transform: translateY(-1px);
                            box-shadow: 0 8px 32px -4px rgba(249, 115, 22, 0.6) !important;
                        }
                        .fi-simple-layout .fi-btn-primary:active {
                            transform: translateY(0);
                        }

                        /* ── Logo glow ── */
                        .fi-simple-layout .fi-logo img,
                        .fi-simple-layout .fi-simple-header img {
                            filter: drop-shadow(0 0 16px rgba(249, 115, 22, 0.35));
                        }

                        /* ── Password reveal icon ── */
                        .fi-simple-layout .fi-input-wrp button {
                            color: #6b6f85 !important;
                        }
                        .fi-simple-layout .fi-input-wrp button:hover {
                            color: #f97316 !important;
                        }
                    </style>
                    <script>
                    /* ── Inject loader immediately on script parse ── */
                    (function() {
                        var loader = document.createElement("div");
                        loader.id = "ag-page-loader";
                        loader.innerHTML = "<div class=\"ag-loader-dots\"></div><div class=\"ag-loader-text\">Memuat...</div>";
                        document.documentElement.appendChild(loader);

                        function hideLoader() {
                            var l = document.getElementById("ag-page-loader");
                            if (l) {
                                l.classList.add("ag-loader-hidden");
                                setTimeout(function() { if (l.parentNode) l.parentNode.removeChild(l); }, 450);
                            }
                        }

                        window.addEventListener("load", function() { setTimeout(hideLoader, 200); });

                        /* Also hide on Livewire navigate */
                        document.addEventListener("livewire:navigated", hideLoader);
                        document.addEventListener("livewire:navigate", function() {
                            var l = document.getElementById("ag-page-loader");
                            if (!l) {
                                var newLoader = document.createElement("div");
                                newLoader.id = "ag-page-loader";
                                newLoader.innerHTML = "<div class=\"ag-loader-dots\"></div><div class=\"ag-loader-text\">Memuat...</div>";
                                document.documentElement.appendChild(newLoader);
                            }
                        });
                    })();

                    document.addEventListener("DOMContentLoaded", function() {
                        var layout = document.querySelector(".fi-simple-layout");
                        if (!layout) return;

                        /* ── Inject background layer with orbs ── */
                        var bgLayer = document.createElement("div");
                        bgLayer.className = "ag-bg-layer";
                        var orbClasses = ["ag-orb ag-orb-1","ag-orb ag-orb-2","ag-orb ag-orb-3","ag-orb ag-orb-4"];
                        for (var i = 0; i < orbClasses.length; i++) {
                            var orb = document.createElement("div");
                            orb.className = orbClasses[i];
                            bgLayer.appendChild(orb);
                        }
                        layout.insertBefore(bgLayer, layout.firstChild);

                        /* ── Inject grid overlay ── */
                        var grid = document.createElement("div");
                        grid.className = "ag-grid";
                        layout.insertBefore(grid, layout.firstChild);

                        /* ── Inject mouse-tracking glow ── */
                        var glow = document.createElement("div");
                        glow.className = "ag-mouse-glow";
                        glow.style.left = "50%";
                        glow.style.top = "50%";
                        layout.appendChild(glow);

                        /* ── Mouse tracking ── */
                        document.addEventListener("mousemove", function(e) {
                            glow.style.left = e.clientX + "px";
                            glow.style.top = e.clientY + "px";
                        });
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
