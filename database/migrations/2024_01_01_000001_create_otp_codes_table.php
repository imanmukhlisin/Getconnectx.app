<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Channel: email atau whatsapp
            $table->enum('channel', ['email', 'whatsapp']);

            // 6-digit OTP code (stored as hashed string)
            $table->string('code', 6);

            // Expiry timestamp
            $table->timestamp('expires_at');

            // Waktu ketika OTP ini berhasil diverifikasi
            $table->timestamp('verified_at')->nullable();

            // Rate limit trackers
            $table->unsignedTinyInteger('send_count')->default(1);
            $table->timestamp('send_window_start')->nullable();

            $table->timestamps();

            // Index for fast lookup
            $table->index(['user_id', 'channel', 'verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
