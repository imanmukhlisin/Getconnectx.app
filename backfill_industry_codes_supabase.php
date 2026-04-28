<?php

/**
 * SCRIPT STANDALONE — Backfill `code` untuk industry tags (in_N)
 */

$host     = 'aws-1-ap-southeast-2.pooler.supabase.com';
$port     = '6543';
$dbname   = 'postgres';
$user     = 'postgres.mvrlrtjxnzrsskernrat';
$password = 'getconnectx2026';

$pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
echo "✅ Koneksi ke Supabase berhasil.\n\n";

$industryWithoutCode = $pdo->query(
    "SELECT id, name FROM tags WHERE type = 'industry' AND code IS NULL ORDER BY id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($industryWithoutCode)) {
    echo "ℹ️  Semua industry tags sudah punya code.\n";
    exit(0);
}

echo "🔍 Ditemukan " . count($industryWithoutCode) . " industry tags tanpa code. Mulai backfill...\n\n";

$stmt = $pdo->prepare("UPDATE tags SET code = ? WHERE id = ?");
$counter = 1;
foreach ($industryWithoutCode as $tag) {
    $code = 'in_' . $counter;
    $stmt->execute([$code, $tag['id']]);
    echo "   ✅ [{$code}] {$tag['name']} (id={$tag['id']})\n";
    $counter++;
}

echo "\n🎉 Backfill selesai! " . ($counter - 1) . " industry tags sekarang punya code in_N.\n";
