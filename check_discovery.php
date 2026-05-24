<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIAGNOSTIC REPORT: DISCOVERY STARTUPS ===\n\n";

$startupsCount = \App\Models\Startup::count();
echo "1. Total Startups in Database: " . $startupsCount . "\n";

if ($startupsCount === 0) {
    echo "=> ALASAN: Database kamu tidak memiliki data startup sama sekali. Pastikan kamu sudah membuat startup atau menjalankan seeder jika ada.\n\n";
} else {
    $startupsWithoutLocation = \App\Models\Startup::whereNull('latitude')->orWhereNull('longitude')->count();
    echo "2. Startups TANPA Latitude/Longitude: " . $startupsWithoutLocation . "\n";
    if ($startupsWithoutLocation > 0) {
        echo "=> CATATAN: Filter Discovery membutuhkan latitude & longitude. Startup tanpa lokasi akan disembunyikan jika filter lokasi aktif.\n";
    }

    echo "\n3. Detail 5 Startup Pertama:\n";
    $startups = \App\Models\Startup::take(5)->get();
    foreach ($startups as $s) {
        echo "- {$s->name} (Owner ID: {$s->owner_id})\n";
        echo "  Lokasi: " . ($s->latitude ? "{$s->latitude}, {$s->longitude}" : "KOSONG") . "\n";
        echo "  Industri: {$s->industry}\n";
        echo "  Stage: {$s->stage}\n";
    }
}

echo "\n=============================================\n";
echo "Jalankan script ini dengan: php check_discovery.php\n";
