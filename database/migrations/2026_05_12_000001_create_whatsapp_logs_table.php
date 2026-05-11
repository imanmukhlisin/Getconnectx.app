<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone');
            $table->string('recipient_name')->nullable();
            $table->text('message');
            $table->string('message_type')->default('text'); // text | template | media
            $table->string('status')->default('pending');    // pending | sent | failed | delivered | read
            $table->string('provider')->nullable();          // saungwa | fonnte | meta | twilio
            $table->string('category')->default('otp');      // otp | blast | notification | manual
            $table->string('event_trigger')->nullable();     // otp_login | new_match | blast, etc.
            $table->text('error_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('message_id')->nullable();        // ID dari provider
            $table->unsignedBigInteger('blast_id')->nullable(); // references whatsapp_blasts.id (soft FK)
            $table->unsignedBigInteger('sent_by_admin_id')->nullable(); // admin yg kirim manual
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index(['recipient_phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
