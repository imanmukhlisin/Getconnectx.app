<?php

/**
 * ============================================================
 * SCRIPT MIGRASI STANDALONE — SUPABASE (LinkedIn Sync Tables)
 * ============================================================
 *
 * Script ini membuat:
 *  1. Tabel `user_credentials` (baru)
 *  2. Kolom `last_device_id` di tabel `users` (ALTER TABLE)
 *  3. Menandai kedua migrasi ini di tabel `migrations` Laravel
 *
 * Cara jalankan:
 *   php migrate_linkedin_supabase.php
 *
 * PERINGATAN: Jangan jalankan lebih dari sekali — sudah ada pengecekan idempotent.
 */

// ─── Koneksi ke Supabase ───────────────────────────────────────────────────

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
    echo "✅ Koneksi ke Supabase berhasil.\n\n";
} catch (PDOException $e) {
    die("❌ Koneksi gagal: " . $e->getMessage() . "\n");
}

// ─── Helper ───────────────────────────────────────────────────────────────

function runSQL(PDO $pdo, string $label, string $sql): void
{
    try {
        $pdo->exec($sql);
        echo "✅ {$label}\n";
    } catch (PDOException $e) {
        echo "⚠️  {$label} — SKIP (mungkin sudah ada): " . $e->getMessage() . "\n";
    }
}

function markMigration(PDO $pdo, string $migration): void
{
    // Cek apakah sudah ada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
    $stmt->execute([$migration]);
    if ($stmt->fetchColumn() > 0) {
        echo "ℹ️  Migration '{$migration}' sudah tercatat di tabel migrations. Skip.\n";
        return;
    }

    // Dapatkan batch number tertinggi
    $batch = (int) $pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations")->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
    $stmt->execute([$migration, $batch]);
    echo "📝 Migration '{$migration}' dicatat di tabel migrations (batch {$batch}).\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// MIGRASI 1: Buat tabel user_credentials
// ═══════════════════════════════════════════════════════════════════════════

echo "─── Migrasi 1: Membuat tabel user_credentials ───\n";

runSQL($pdo, 'CREATE TABLE user_credentials', "
    CREATE TABLE IF NOT EXISTS user_credentials (
        id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id     UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        provider    VARCHAR(50) NOT NULL DEFAULT 'linkedin',
        experience  JSONB NOT NULL DEFAULT '[]'::jsonb,
        education   JSONB NOT NULL DEFAULT '[]'::jsonb,
        raw_data    JSONB,
        created_at  TIMESTAMP(0) WITH TIME ZONE DEFAULT NOW(),
        updated_at  TIMESTAMP(0) WITH TIME ZONE DEFAULT NOW(),
        CONSTRAINT user_credentials_user_id_provider_unique UNIQUE (user_id, provider)
    );
");

runSQL($pdo, 'CREATE index on user_credentials(user_id)', "
    CREATE INDEX IF NOT EXISTS idx_user_credentials_user_id ON user_credentials(user_id);
");

markMigration($pdo, '2026_04_27_000001_create_user_credentials_table');

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════
// MIGRASI 2: Tambah kolom last_device_id ke tabel users
// ═══════════════════════════════════════════════════════════════════════════

echo "─── Migrasi 2: Menambah kolom last_device_id ke tabel users ───\n";

// Cek apakah kolom sudah ada sebelum ALTER TABLE
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_name = 'users' AND column_name = 'last_device_id'
");
$stmt->execute();
$columnExists = (int) $stmt->fetchColumn() > 0;

if ($columnExists) {
    echo "ℹ️  Kolom 'last_device_id' sudah ada di tabel users. Skip ALTER TABLE.\n";
} else {
    runSQL($pdo, "ALTER TABLE users ADD COLUMN last_device_id", "
        ALTER TABLE users ADD COLUMN IF NOT EXISTS last_device_id VARCHAR(255) NULL;
    ");
}

markMigration($pdo, '2026_04_27_000002_add_last_device_id_to_users_table');

echo "\n";

// ─── Selesai ───────────────────────────────────────────────────────────────

echo "═══════════════════════════════════════════════════\n";
echo "🎉 Semua migrasi selesai! Supabase sudah siap.\n";
echo "   - Tabel user_credentials: READY\n";
echo "   - Kolom users.last_device_id: READY\n";
echo "═══════════════════════════════════════════════════\n";
