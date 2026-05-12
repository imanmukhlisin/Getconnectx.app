<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix tabel messages:
     *   1. Jadikan kolom `content` nullable (kirim gambar tidak butuh teks)
     *   2. Tambahkan kolom `media` (jsonb) untuk menyimpan url, mime_type, size_bytes
     *   3. Tambahkan kolom `read_at` (timestamp) untuk read-receipt
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // 1. Buat content nullable (image message tidak perlu content)
            $table->text('content')->nullable()->change();

            // 2. Kolom media — store as JSON (url, thumbnail_url, mime_type, size_bytes)
            if (!Schema::hasColumn('messages', 'media')) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    // PostgreSQL: pakai jsonb supaya bisa di-index & di-query
                    $table->jsonb('media')->nullable()->after('is_read');
                } else {
                    $table->json('media')->nullable()->after('is_read');
                }
            }

            // 3. Kolom read_at — timestamp read-receipt per message
            if (!Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('media');
            }
        });
    }

    /**
     * Rollback: kembalikan content NOT NULL, hapus media & read_at.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('content')->nullable(false)->change();

            if (Schema::hasColumn('messages', 'media')) {
                $table->dropColumn('media');
            }

            if (Schema::hasColumn('messages', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
};
