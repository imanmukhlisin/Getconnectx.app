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

        // Override storage path ke /tmp jika storage default tidak writable.
        // Di Vercel, /var/task/ adalah read-only filesystem — hanya /tmp yang writable.
        // Pengecekan langsung via is_writable() lebih robust daripada cek env var.
        $defaultStorage = base_path('storage');
        if (!is_writable($defaultStorage) && is_writable('/tmp')) {
            $tmpStorage = env('APP_STORAGE_PATH', '/tmp/storage');

            // Buat semua direktori yang diperlukan Laravel di /tmp
            foreach ([
                '/framework/cache/data',
                '/framework/sessions',
                '/framework/views',
                '/framework/testing',
                '/logs',
                '/app/public',
            ] as $dir) {
                $path = $tmpStorage . $dir;
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
            }

            $this->app->useStoragePath($tmpStorage);
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
