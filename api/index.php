<?php

/**
 * Vercel Serverless PHP Entry Point (Bridge)
 *
 * File ini berfungsi sebagai jembatan (bridge) antara runtime Vercel
 * dan entry point standar Laravel. Vercel akan mengeksekusi file ini,
 * kemudian Laravel akan menangani request melalui public/index.php.
 */

// Fix path untuk storage dan public di lingkungan Vercel read-only.
// Variabel ini bisa di-override via Environment Variables di dashboard Vercel.
if (!isset($_ENV['APP_STORAGE_PATH'])) {
    // Di Vercel, /tmp adalah satu-satunya direktori yang writable.
    $_ENV['APP_STORAGE_PATH'] = '/tmp/storage';
}

// Pastikan direktori storage yang diperlukan ada di /tmp
$storagePath = $_ENV['APP_STORAGE_PATH'];
$dirs = [
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/framework/views',
    $storagePath . '/framework/testing',
    $storagePath . '/logs',
    $storagePath . '/app/public',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Arahkan ke entry point utama Laravel.
require __DIR__ . '/../public/index.php';
