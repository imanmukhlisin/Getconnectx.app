<?php

$tmpBase = '/tmp/laravel';
$dirs = [
    $tmpBase . '/framework/cache/data',
    $tmpBase . '/framework/sessions',
    $tmpBase . '/framework/views',
    $tmpBase . '/framework/testing',
    $tmpBase . '/logs',
    $tmpBase . '/app/public',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

putenv("APP_STORAGE_PATH={$tmpBase}");
$_ENV['APP_STORAGE_PATH'] = $tmpBase;
putenv("VIEW_COMPILED_PATH={$tmpBase}/framework/views");
$_ENV['VIEW_COMPILED_PATH'] = "{$tmpBase}/framework/views";

// FIX: Vercel-PHP runtime mengatur SCRIPT_NAME ke '/api/index.php'.
// Framework (Symfony HttpFoundation) menggunakan direktori dari SCRIPT_NAME
// sebagai "Base Path" (yaitu '/api'). Base path ini kemudian dipotong otomatis dari REQUEST_URI.
// Akibatnya REQUEST_URI='/api/v1/auth/register' di-strip menjadi path '/v1/auth/register'
// sehingga Laravel merespon 404 (dia butuhnya '/api/v1/...').
// Solusi: Kita nipu SCRIPT_NAME seakan-akan aplikasi dijalankan dari root folder ('/')
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php' . ($_SERVER['PATH_INFO'] ?? '');

// FIX: Force HTTPS on Vercel so Filament/Livewire generate correct asset URLs
$_SERVER['HTTPS'] = 'on';
putenv("APP_URL=https://getconnectxapp.vercel.app");
$_ENV['APP_URL'] = 'https://getconnectxapp.vercel.app';

require __DIR__ . '/../public/index.php';
