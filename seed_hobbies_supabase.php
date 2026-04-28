<?php

/**
 * SCRIPT STANDALONE — Restructure Tags (Personality & Hobbies)
 * 
 * 1. Tambah kolom `code` ke tabel `tags` (ALTER TABLE)
 * 2. Hapus tags personality/hobby yang salah (hasil gemini flash)
 * 3. Insert ulang PERSIS sesuai API-PROFILE-LINKEDIN.md contract
 */

$host     = 'aws-1-ap-southeast-2.pooler.supabase.com';
$port     = '6543';
$dbname   = 'postgres';
$user     = 'postgres.mvrlrtjxnzrsskernrat';
$password = 'getconnectx2026';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ Koneksi ke Supabase berhasil.\n\n";
} catch (PDOException $e) {
    die("❌ Koneksi gagal: " . $e->getMessage() . "\n");
}

// ── Step 1: Tambah kolom `code` jika belum ada ─────────────────────────────
echo "─── Step 1: Cek kolom `code` di tabel tags ───\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name='tags' AND column_name='code'");
$exists = (int) $stmt->fetchColumn() > 0;

if ($exists) {
    echo "ℹ️  Kolom `code` sudah ada. Skip.\n";
} else {
    $pdo->exec("ALTER TABLE tags ADD COLUMN code VARCHAR(50) NULL");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS tags_code_unique ON tags(code) WHERE code IS NOT NULL");
    echo "✅ Kolom `code` berhasil ditambahkan.\n";
}

// Catat di migrations Laravel
$migName = '2026_04_29_000001_add_code_to_tags_table';
$check = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
$check->execute([$migName]);
if ((int) $check->fetchColumn() === 0) {
    $batch = (int) $pdo->query("SELECT COALESCE(MAX(batch),0)+1 FROM migrations")->fetchColumn();
    $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?,?)")->execute([$migName, $batch]);
    echo "📝 Migration '{$migName}' dicatat (batch {$batch}).\n";
} else {
    echo "ℹ️  Migration sudah tercatat.\n";
}

echo "\n";

// ── Step 2: Hapus tags personality/hobby yang salah ────────────────────────
echo "─── Step 2: Hapus tags personality/hobby yang tidak sesuai kontrak ───\n";
$pdo->exec("DELETE FROM tags WHERE type IN ('personality_hobbies','hobby','personality')");
echo "✅ Tags personality/hobby lama dihapus.\n\n";

// ── Step 3: Insert ulang sesuai API Contract ────────────────────────────────
echo "─── Step 3: Insert personality & hobbies sesuai API-PROFILE-LINKEDIN.md ───\n";

// Data PERSIS dari kontrak API-PROFILE-LINKEDIN.md (baris 312-319)
// Diexpand untuk mencakup lebih banyak pilihan — tetap dengan format kode ph_N
$personalityAndHobbies = [
    ['code' => 'ph_1',  'name' => 'Goal-Oriented'],
    ['code' => 'ph_2',  'name' => 'Problem Solver'],
    ['code' => 'ph_3',  'name' => 'Coffee Enthusiast'],
    ['code' => 'ph_4',  'name' => 'Avid Reader'],
    ['code' => 'ph_5',  'name' => 'Marathon Runner'],
    ['code' => 'ph_6',  'name' => 'Guitar Player'],
    ['code' => 'ph_7',  'name' => 'Creative Thinker'],
    ['code' => 'ph_8',  'name' => 'Team Player'],
    ['code' => 'ph_9',  'name' => 'Strategic Planner'],
    ['code' => 'ph_10', 'name' => 'Risk Taker'],
    ['code' => 'ph_11', 'name' => 'Tech Enthusiast'],
    ['code' => 'ph_12', 'name' => 'Storyteller'],
    ['code' => 'ph_13', 'name' => 'Traveler'],
    ['code' => 'ph_14', 'name' => 'Film Buff'],
    ['code' => 'ph_15', 'name' => 'Fitness Junkie'],
];

$stmt = $pdo->prepare("INSERT INTO tags (name, type, code, created_at, updated_at) VALUES (?, 'personality_hobbies', ?, NOW(), NOW())");

foreach ($personalityAndHobbies as $tag) {
    $stmt->execute([$tag['name'], $tag['code']]);
    echo "   ✅ Inserted: {$tag['code']} → {$tag['name']}\n";
}

echo "\n";

// ── Verifikasi ─────────────────────────────────────────────────────────────
echo "─── Verifikasi Data ───\n";
$rows = $pdo->query("SELECT code, name, type FROM tags WHERE type = 'personality_hobbies' ORDER BY code")->fetchAll(PDO::FETCH_ASSOC);
echo "Total personality_hobbies tags: " . count($rows) . "\n";
foreach ($rows as $row) {
    echo "   [{$row['code']}] {$row['name']}\n";
}

echo "\n═══════════════════════════════════════════════════\n";
echo "🎉 Restructure selesai! Tags sekarang sesuai kontrak.\n";
echo "═══════════════════════════════════════════════════\n";
