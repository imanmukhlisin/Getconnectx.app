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
        // Tanpa binding ini, Laravel tidak dapat menemukan direktori public
        // karena working directory di Vercel berbeda dari struktur standar.
        $this->app->bind('path.public', function () {
            return base_path('public');
        });

        // Override storage path ke /tmp jika berjalan di Vercel.
        // /tmp adalah satu-satunya direktori yang writable di lingkungan Vercel.
        if (!empty($_ENV['VERCEL']) || !empty(getenv('VERCEL'))) {
            $storagePath = env('APP_STORAGE_PATH', '/tmp/storage');
            $this->app->useStoragePath($storagePath);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
