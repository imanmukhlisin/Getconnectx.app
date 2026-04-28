<?php

/**
 * SCRIPT STANDALONE — Backfill `code` untuk skill tags yang sudah ada di DB
 * Assign sk_1, sk_2, ... ke semua tags bertipe 'skill' yang code-nya NULL
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

// ── Ambil semua skill tags yang belum punya code ───────────────────────────
$skillsWithoutCode = $pdo->query(
    "SELECT id, name FROM tags WHERE type = 'skill' AND code IS NULL ORDER BY id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($skillsWithoutCode)) {
    echo "ℹ️  Semua skill tags sudah punya code. Tidak ada yang perlu diupdate.\n";
    exit(0);
}

echo "🔍 Ditemukan " . count($skillsWithoutCode) . " skill tags tanpa code. Mulai backfill...\n\n";

$updateStmt = $pdo->prepare("UPDATE tags SET code = ? WHERE id = ?");

$counter = 1;
foreach ($skillsWithoutCode as $tag) {
    $code = 'sk_' . $counter;
    $updateStmt->execute([$code, $tag['id']]);
    echo "   ✅ [{$code}] {$tag['name']} (id={$tag['id']})\n";
    $counter++;
}

echo "\n═══════════════════════════════════════════════════\n";
echo "🎉 Backfill selesai! " . ($counter - 1) . " skill tags sudah punya code sk_N.\n";
echo "═══════════════════════════════════════════════════\n";
