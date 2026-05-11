<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_blasts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // Nama campaign
            $table->text('message');                         // Isi pesan broadcast
            $table->string('status')->default('draft');      // draft | scheduled | running | completed | failed | cancelled
            $table->string('target_segment')->default('all'); // all | onboarded | new_users | custom
            $table->json('target_filters')->nullable();      // Filter tambahan (e.g. kota, role)
            $table->json('recipient_phones')->nullable();    // List nomor custom
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_blasts');
    }
};
