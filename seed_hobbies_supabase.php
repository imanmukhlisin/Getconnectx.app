<?php

/**
 * SCRIPT STANDALONE SEEDING - PERSONALITY & HOBBIES
 */

$host     = 'aws-1-ap-southeast-2.pooler.supabase.com';
$port     = '6543';
$dbname   = 'postgres';
$user     = 'postgres.mvrlrtjxnzrsskernrat';
$password = 'getconnectx2026';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ Koneksi ke Supabase berhasil.\n";
} catch (PDOException $e) {
    die("❌ Koneksi gagal: " . $e->getMessage() . "\n");
}

$tags = [
    // Hobbies
    ['Gaming', 'personality_hobbies'], ['Hiking', 'personality_hobbies'], ['Photography', 'personality_hobbies'],
    ['Cooking', 'personality_hobbies'], ['Traveling', 'personality_hobbies'], ['Reading', 'personality_hobbies'],
    ['Fitness', 'personality_hobbies'], ['Music', 'personality_hobbies'], ['Coding', 'personality_hobbies'],
    ['Art', 'personality_hobbies'], ['Fashion', 'personality_hobbies'], ['Movies', 'personality_hobbies'],
    ['Sports', 'personality_hobbies'], ['Yoga', 'personality_hobbies'], ['Writing', 'personality_hobbies'],
    // Personalities
    ['Introvert', 'personality'], ['Extrovert', 'personality'], ['Analytical', 'personality'],
    ['Creative', 'personality'], ['Organized', 'personality'], ['Risk Taker', 'personality'],
    ['Problem Solver', 'personality'], ['Team Player', 'personality'], ['Leader', 'personality'],
    ['Empathetic', 'personality']
];

echo "🚚 Sedang memasukkan data tags...\n";

$stmt = $pdo->prepare("INSERT INTO tags (name, type, created_at, updated_at) VALUES (?, ?, NOW(), NOW()) ON CONFLICT DO NOTHING");

$count = 0;
foreach ($tags as $tag) {
    // Cek dulu apakah sudah ada
    $check = $pdo->prepare("SELECT id FROM tags WHERE name = ? AND type = ?");
    $check->execute([$tag[0], $tag[1]]);
    if (!$check->fetch()) {
        $stmt->execute([$tag[0], $tag[1]]);
        $count++;
    }
}

echo "🎉 Sukses! {$count} data master baru berhasil dimasukkan ke tabel tags.\n";
