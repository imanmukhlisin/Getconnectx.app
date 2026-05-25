<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Ambil user pertama yang sudah onboarding
$user = \App\Models\User::where('is_onboarded', true)->first();

if (!$user) {
    die("❌ Belum ada user yang selesai onboarding di database.\n");
}

echo "👤 Login sebagai User: {$user->name} (ID: {$user->id})\n";
echo "📍 Lokasi User  : {$user->city} (Lat: {$user->latitude}, Lon: {$user->longitude})\n\n";

// 2. Simulasi Request POST ke DiscoveryController
$payload = [
    'context' => [
        'mode' => 'explore_startups'
    ],
    'filters' => [],
    'pagination' => [
        'limit' => 10
    ]
];

// Buat instance Request dengan body JSON
$request = \Illuminate\Http\Request::create('/api/v1/discovery/cards', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

// Bind Request ke dalam container (Penting untuk FormRequest validation)
$app->instance('request', $request);

// Override auth user
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Auth::login($user) untuk memastikan middleware/guard mendeteksinya
\Illuminate\Support\Facades\Auth::login($user);

// 3. Panggil Controller melalui Internal Router
try {
    $response = app()->handle($request);
    $data = json_decode($response->getContent(), true);
    
    // 4. Tampilkan Hasilnya
    echo "=== HASIL DISCOVERY CARDS (STARTUP) ===\n\n";
    
    if (isset($data['success']) && $data['success'] === false) {
        echo "❌ Error API: " . ($data['message'] ?? 'Unknown Error') . "\n";
        print_r($data);
        exit;
    }

    if (empty($data['data']['items'])) {
        echo "⚠️ Hasil kosong (0 cards).\n";
        echo "Kemungkinan penyebab:\n";
        echo "- Belum ada data startup lain di database.\n";
        echo "- Semua startup yang ada adalah milik user ini sendiri.\n";
        echo "- Filter jarak/industri menolak data.\n";
        
        echo "\n[DEBUG RESPONSE UTUH]:\n";
        print_r($data);
    } else {
        echo "✅ Menemukan " . count($data['data']['items']) . " Startup:\n\n";
        
        foreach ($data['data']['items'] as $index => $card) {
            $jarak = isset($card['location']['distanceKm']) ? $card['location']['distanceKm'] . " KM" : "Remote / Tidak Diketahui";
            
            echo ($index + 1) . ". {$card['name']} (ID: {$card['startupId']})\n";
            echo "   - Industri : " . ($card['industry']['display'] ?? '-') . "\n";
            echo "   - Stage    : " . ($card['teamStage']['stage'] ?? '-') . "\n";
            echo "   - Jarak    : {$jarak}\n";
            echo "   - Founder  : " . ($card['founder']['name'] ?? '-') . "\n";
            echo "----------------------------------------\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Terjadi Error di Backend: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\nJalankan ulang dengan: php test_cards.php\n";
