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

require __DIR__ . '/../public/index.php';
