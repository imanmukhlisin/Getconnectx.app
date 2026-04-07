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
     */
    public function boot(): void
    {
        //
    }
}
