<?php

/**
 * Vercel Serverless PHP Entry Point (Bridge)
 *
 * Set semua path ke /tmp SEBELUM Laravel boot agar config/view.php
 * dan config/session.php membaca path yang writable sejak awal.
 */

// Semua direktori yang perlu Laravel untuk bisa write
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
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set env vars SEBELUM Laravel load config
// Sehingga config/view.php dan path lainnya langsung pakai /tmp
putenv("APP_STORAGE_PATH={$tmpBase}");
$_ENV['APP_STORAGE_PATH'] = $tmpBase;

putenv("VIEW_COMPILED_PATH={$tmpBase}/framework/views");
$_ENV['VIEW_COMPILED_PATH'] = "{$tmpBase}/framework/views";

// Arahkan ke entry point utama Laravel
require __DIR__ . '/../public/index.php';
