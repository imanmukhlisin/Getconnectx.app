<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_webhook_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default');        // Nama konfigurasi
            $table->string('provider')->default('meta');       // meta | saungwa | fonnte
            // ── Meta / WABA ──────────────────────────────────────────
            $table->string('phone_number_id')->nullable();     // Meta Phone Number ID
            $table->string('waba_id')->nullable();             // WhatsApp Business Account ID
            $table->text('access_token')->nullable();          // Meta Access Token (enkripsi)
            $table->string('app_secret')->nullable();          // Meta App Secret (untuk verifikasi webhook)
            $table->string('verify_token')->nullable();        // Webhook Verify Token (custom string)
            $table->string('api_version')->default('v19.0');   // Meta Graph API version
            // ── Status ────────────────────────────────────────────────
            $table->boolean('is_active')->default(false);
            $table->boolean('webhook_verified')->default(false);
            $table->timestamp('last_webhook_at')->nullable();  // Kapan terakhir menerima webhook
            $table->timestamp('token_expires_at')->nullable(); // Token expiry
            $table->json('webhook_fields')->nullable();        // Fields yang di-subscribe (messages, status)
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_configs');
    }
};
