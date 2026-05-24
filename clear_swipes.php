<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// User ID Mas Dimas berdasarkan output sebelumnya
$userId = 'a1c3e0df-3691-44c1-bd0a-13de8b773ebf';

// Hapus semua riwayat skip/like dari user tersebut
$count = \App\Models\Like::where('from_user_id', $userId)->delete();

echo "Berhasil menghapus {$count} riwayat skip/like untuk user ID: {$userId}.\n";
echo "Sekarang data startup yang udah di-skip bakal muncul lagi di Discovery!\n";
