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

// FIX: Pastikan Livewire/Filament menulis komponen cache-nya di folder /tmp
putenv("LIVEWIRE_MANIFEST_PATH={$tmpBase}/livewire-components.php");
$_ENV['LIVEWIRE_MANIFEST_PATH'] = "{$tmpBase}/livewire-components.php";

// FIX: Vercel-PHP runtime mengatur SCRIPT_NAME ke '/api/index.php'.
// Framework (Symfony HttpFoundation) menggunakan direktori dari SCRIPT_NAME
// sebagai "Base Path" (yaitu '/api'). Base path ini kemudian dipotong otomatis dari REQUEST_URI.
// Akibatnya REQUEST_URI='/api/v1/auth/register' di-strip menjadi path '/v1/auth/register'
// sehingga Laravel merespon 404 (dia butuhnya '/api/v1/...').
// Solusi: Kita nipu SCRIPT_NAME seakan-akan aplikasi dijalankan dari root folder ('/')
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php' . ($_SERVER['PATH_INFO'] ?? '');

// FIX: Force HTTPS on Vercel to ensure assets use https://
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// FIX: Enable gzip compression to avoid Vercel's 6MB response payload limit
// Filament admin pages can be large; this reduces payload by ~70%
if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    ob_start('ob_gzhandler');
}

try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'stack_trace_top' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5)
    ], JSON_PRETTY_PRINT);
    exit;
}
