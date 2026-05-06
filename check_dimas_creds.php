<?php
$host = 'aws-1-ap-southeast-2.pooler.supabase.com';
$port = 6543; $dbname = 'postgres';
$user = 'postgres.mvrlrtjxnzrsskernrat'; $password = 'getconnectx2026';
$pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "All users matching Dimas:\n";
$stmt = $pdo->prepare("SELECT id, name, email, linkedin_url, created_at FROM users WHERE name ILIKE '%dimas%' OR name ILIKE '%oktavian%' ORDER BY created_at DESC");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nAll user_credentials:\n";
$stmt = $pdo->prepare("SELECT user_id, provider, created_at FROM user_credentials");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
