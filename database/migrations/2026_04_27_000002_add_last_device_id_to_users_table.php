<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom last_device_id ke tabel users.
 *
 * Kolom ini digunakan untuk menyimpan device ID terakhir yang digunakan user
 * saat melakukan LinkedIn sync, berguna untuk keamanan dan analytic device tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Device ID terakhir yang digunakan saat sync LinkedIn
            $table->string('last_device_id')->nullable()->after('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_device_id');
        });
    }
};
