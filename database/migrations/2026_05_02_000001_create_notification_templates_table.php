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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Identifier for the template (e.g., account_created)');
            $table->string('type')->default('push')->comment('push, email, in_app');
            $table->jsonb('title')->nullable()->comment('Localized title {"en": "...", "id": "..."}');
            $table->jsonb('body')->nullable()->comment('Localized body {"en": "...", "id": "..."}');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
