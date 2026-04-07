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

// DEBUG ENDPOINT — Hapus setelah selesai diagnosa
if (($_SERVER['REQUEST_URI'] ?? '') === '/_debug') {
    header('Content-Type: application/json');
    echo json_encode([
        'REQUEST_URI'    => $_SERVER['REQUEST_URI'] ?? null,
        'SCRIPT_NAME'    => $_SERVER['SCRIPT_NAME'] ?? null,
        'PATH_INFO'      => $_SERVER['PATH_INFO'] ?? null,
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
        'HTTP_HOST'      => $_SERVER['HTTP_HOST'] ?? null,
    ], JSON_PRETTY_PRINT);
    exit;
}

require __DIR__ . '/../public/index.php';
