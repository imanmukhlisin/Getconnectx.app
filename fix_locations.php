<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Startup;

echo "=== MEMPERBAIKI DATA LOKASI USER & STARTUP LAMA ===\n\n";

function fetchCoordinate($city, $country = 'Indonesia') {
    $searchQuery = urlencode($city . ($country ? ', ' . $country : ''));
    try {
        $response = Http::withHeaders([
            'User-Agent' => 'ConnectX-App/1.0'
        ])->timeout(10)->get("https://nominatim.openstreetmap.org/search?q={$searchQuery}&format=json&limit=1");

        if ($response->successful() && !empty($response->json())) {
            $data = $response->json()[0];
            return [
                'lat' => $data['lat'] ?? null,
                'lon' => $data['lon'] ?? null,
            ];
        }
    } catch (\Throwable $e) {
        // Abaikan jika error
    }
    return null;
}

// 1. UPDATE USERS
$users = User::whereNull('latitude')->whereNotNull('city')->get();
echo "Memproses " . $users->count() . " User...\n";

foreach ($users as $user) {
    echo "- Mengambil kordinat untuk User [{$user->name}] di [{$user->city}]... ";
    $coords = fetchCoordinate($user->city, $user->country ?? 'Indonesia');
    
    if ($coords && $coords['lat']) {
        $user->update([
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon'],
        ]);
        echo "✅ OK ({$coords['lat']}, {$coords['lon']})\n";
    } else {
        echo "❌ Gagal\n";
    }
    sleep(1); // Mencegah blokir IP dari OSM (Limit 1 request per detik)
}

echo "\n-----------------------------------\n";

// 2. UPDATE STARTUPS
$startups = Startup::whereNull('latitude')->with('owner')->get();
echo "Memproses " . $startups->count() . " Startup...\n";

foreach ($startups as $startup) {
    echo "- Menyalin kordinat untuk Startup [{$startup->name}] dari Owner-nya... ";
    
    // Karena startup lama belum punya data city, kita copy paste langsung dari user owner-nya
    if ($startup->owner && $startup->owner->latitude) {
        $startup->update([
            'city'      => $startup->owner->city,
            'country'   => $startup->owner->country,
            'latitude'  => $startup->owner->latitude,
            'longitude' => $startup->owner->longitude,
        ]);
        echo "✅ OK ({$startup->owner->latitude}, {$startup->owner->longitude})\n";
    } else {
        echo "❌ Gagal (Owner belum punya lokasi)\n";
    }
}

echo "\n✅ SELESAI! Semua data lama sekarang sudah punya kordinat GPS.\n";
