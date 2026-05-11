<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Binding path.public secara manual diperlukan agar Laravel dapat
     * menemukan dan memuat aset dengan benar di lingkungan Vercel
     * yang menggunakan arsitektur serverless.
     */
    public function register(): void
    {
        // Binding path.public untuk kompatibilitas Vercel serverless.
        $this->app->bind('path.public', function () {
            return base_path('public');
        });

        // Override storage path jika APP_STORAGE_PATH di-set oleh api/index.php.
        // CATATAN: is_writable() TIDAK bisa dipakai karena di Vercel,
        // filesystem /var/task/ punya permission bits writable tapi sebenarnya
        // read-only (EROFS). Kita pakai getenv() yang di-set eksplisit
        // sebelum Laravel boot melalui putenv() di api/index.php.
        $tmpStorage = getenv('APP_STORAGE_PATH');
        if ($tmpStorage) {
            $this->app->useStoragePath($tmpStorage);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * VERCEL FIX: Karena Vercel serverless tidak bisa menulis cache dan tidak
     * support artisan livewire:discover, semua Filament page di-register manual.
     * Ini memastikan Livewire bisa resolve komponen tanpa auto-discovery.
     */
    public function boot(): void
    {
        \URL::forceScheme('https');



        if (class_exists(\Livewire\Livewire::class)) {
            // ── Onboarding Option Resource ────────────────────────────────────────
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-option-resource.pages.create-onboarding-option',
                \App\Filament\Resources\Onboarding\OnboardingOptionResource\Pages\CreateOnboardingOption::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-option-resource.pages.edit-onboarding-option',
                \App\Filament\Resources\Onboarding\OnboardingOptionResource\Pages\EditOnboardingOption::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-option-resource.pages.list-onboarding-options',
                \App\Filament\Resources\Onboarding\OnboardingOptionResource\Pages\ListOnboardingOptions::class
            );

            // ── Onboarding Question Resource ──────────────────────────────────────
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-question-resource.pages.create-onboarding-question',
                \App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages\CreateOnboardingQuestion::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-question-resource.pages.edit-onboarding-question',
                \App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages\EditOnboardingQuestion::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-question-resource.pages.list-onboarding-questions',
                \App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages\ListOnboardingQuestions::class
            );

            // ── Onboarding Step Resource ──────────────────────────────────────────
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-step-resource.pages.create-onboarding-step',
                \App\Filament\Resources\Onboarding\OnboardingStepResource\Pages\CreateOnboardingStep::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-step-resource.pages.edit-onboarding-step',
                \App\Filament\Resources\Onboarding\OnboardingStepResource\Pages\EditOnboardingStep::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.onboarding.onboarding-step-resource.pages.list-onboarding-steps',
                \App\Filament\Resources\Onboarding\OnboardingStepResource\Pages\ListOnboardingSteps::class
            );

            // ── Tag Resource ──────────────────────────────────────────────────────
            \Livewire\Livewire::component(
                'app.filament.resources.tag-resource.pages.create-tag',
                \App\Filament\Resources\TagResource\Pages\CreateTag::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.tag-resource.pages.edit-tag',
                \App\Filament\Resources\TagResource\Pages\EditTag::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.tag-resource.pages.list-tags',
                \App\Filament\Resources\TagResource\Pages\ListTags::class
            );
            // ── WhatsApp Module ───────────────────────────────────────────────────
            \Livewire\Livewire::component(
                'app.filament.pages.whatsapp-management',
                \App\Filament\Pages\WhatsappManagement::class
            );
            \Livewire\Livewire::component(
                'app.filament.widgets.whatsapp-stats-widget',
                \App\Filament\Widgets\WhatsappStatsWidget::class
            );

            // WhatsApp Log Resource
            \Livewire\Livewire::component(
                'app.filament.resources.whatsapp-log-resource.pages.list-whatsapp-logs',
                \App\Filament\Resources\WhatsappLogResource\Pages\ListWhatsappLogs::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.whatsapp-log-resource.pages.view-whatsapp-log',
                \App\Filament\Resources\WhatsappLogResource\Pages\ViewWhatsappLog::class
            );

            // WhatsApp Blast Resource
            \Livewire\Livewire::component(
                'app.filament.resources.whatsapp-blast-resource.pages.list-whatsapp-blasts',
                \App\Filament\Resources\WhatsappBlastResource\Pages\ListWhatsappBlasts::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.whatsapp-blast-resource.pages.create-whatsapp-blast',
                \App\Filament\Resources\WhatsappBlastResource\Pages\CreateWhatsappBlast::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.whatsapp-blast-resource.pages.edit-whatsapp-blast',
                \App\Filament\Resources\WhatsappBlastResource\Pages\EditWhatsappBlast::class
            );
            \Livewire\Livewire::component(
                'app.filament.resources.whatsapp-blast-resource.pages.view-whatsapp-blast',
                \App\Filament\Resources\WhatsappBlastResource\Pages\ViewWhatsappBlast::class
            );
        }
    }
}
