<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Ambil user pertama yang sudah onboarding
$user = \App\Models\User::where('is_onboarded', true)->first();

if (!$user) {
    die("❌ Belum ada user di database.\n");
}

echo "👤 Login sebagai User: {$user->name}\n\n";

// 2. Simulasi Request GET ke Filter Options
$request = \Illuminate\Http\Request::create('/api/v1/discovery/filter-options', 'GET', [
    'mode' => 'finding_cofounder'
]);

// Override auth user
$request->setUserResolver(function () use ($user) {
    return $user;
});
\Illuminate\Support\Facades\Auth::login($user);

// 3. Panggil Controller
try {
    $response = app()->handle($request);
    $data = json_decode($response->getContent(), true);
    
    echo "=== HASIL FILTER OPTIONS (Mode: finding_cofounder) ===\n\n";
    
    if (isset($data['success']) && $data['success'] === false) {
        echo "❌ Error API: " . ($data['message'] ?? 'Unknown Error') . "\n";
        exit;
    }

    $filters = $data['data'] ?? [];
    
    echo "📌 Mode: " . ($filters['mode'] ?? '-') . "\n\n";
    
    // CITY
    if (isset($filters['city'])) {
        echo "[1] CITY (Tipe: {$filters['city']['type']})\n";
        echo "    Placeholder: {$filters['city']['placeholder']}\n";
        echo "    Jumlah Data: " . count($filters['city']['options']) . " kota\n";
        echo "    Contoh 3 Kota Pertama:\n";
        foreach (array_slice($filters['city']['options'], 0, 3) as $opt) {
            echo "      - {$opt['label']} (ID: {$opt['value']})\n";
        }
        echo "\n";
    }

    // INDUSTRIES
    if (isset($filters['industries'])) {
        echo "[2] INDUSTRIES\n";
        echo "    Jumlah Kategori/Grup: " . count($filters['industries']) . "\n";
        foreach (array_slice($filters['industries'], 0, 2) as $grp) {
            echo "    - Grup: {$grp['label']} (" . count($grp['options']) . " opsi)\n";
        }
        echo "    ...\n\n";
    }

    // ROLES
    if (isset($filters['roles'])) {
        echo "[3] ROLES (Co-Founder Type)\n";
        echo "    Jumlah Kategori/Grup: " . count($filters['roles']) . "\n";
        foreach (array_slice($filters['roles'], 0, 2) as $grp) {
            echo "    - Grup: {$grp['label']} (" . count($grp['options']) . " opsi)\n";
        }
        echo "    ...\n\n";
    }
    
    // SKILLS
    if (isset($filters['skills'])) {
        echo "[4] SKILLS\n";
        echo "    Jumlah Kategori/Grup: " . count($filters['skills']) . "\n";
        foreach (array_slice($filters['skills'], 0, 2) as $grp) {
            echo "    - Grup: {$grp['label']} (" . count($grp['options']) . " opsi)\n";
        }
        echo "    ...\n\n";
    }

    // AVAILABILITY & EQUITY
    echo "[5] AVAILABILITY (Grup: " . count($filters['availability'] ?? []) . ")\n";
    echo "[6] EQUITY (Grup: " . count($filters['equity'] ?? []) . ")\n";
    echo "[7] LANGUAGES (Grup: " . count($filters['languages'] ?? []) . ")\n\n";
    
    echo "✅ Berhasil meload Filter Options secara terstruktur!\n";

} catch (\Exception $e) {
    echo "❌ Terjadi Error di Backend: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
