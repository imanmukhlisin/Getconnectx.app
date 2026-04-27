<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi untuk membuat tabel user_credentials.
 *
 * Tabel ini menyimpan data kredensial/profil dari penyedia OAuth (LinkedIn, Google, dll),
 * termasuk riwayat pekerjaan (experience) dan pendidikan (education) yang dikompres menjadi JSON.
 * Dipisahkan dari tabel users untuk menjaga keterbacaan dan skalabilitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->uuid('user_id');
            $table->string('provider')->default('linkedin'); // linkedin, google, github, dll

            // Data Pekerjaan (maks 3 entry terbaru, default array kosong)
            // Format per item: { title, company, period, isCurrent }
            $table->json('experience')->default('[]');

            // Data Pendidikan (default array kosong)
            // Format per item: { degree, school, period }
            $table->json('education')->default('[]');

            // Simpan raw data lengkap dari provider (opsional, untuk debugging)
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Setiap user hanya boleh punya 1 record per provider
            $table->unique(['user_id', 'provider']);

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credentials');
    }
};
