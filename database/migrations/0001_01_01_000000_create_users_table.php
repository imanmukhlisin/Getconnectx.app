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
        Schema::create('users', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            // Entity type – wajib dideklarasikan sejak awal
            $table->enum('entity_type', ['talent', 'startup']);

            // Credentials
            $table->string('email')->unique();
            $table->string('password')->nullable(); // nullable untuk OAuth users
            $table->timestamp('email_verified_at')->nullable();

            // WhatsApp
            $table->string('whatsapp_number', 20)->nullable();
            $table->timestamp('whatsapp_verified_at')->nullable();

            // Registration flow state (1-5, linear lock)
            // 1 = registered, 2 = email otp sent, 3 = email verified,
            // 4 = wa otp sent, 5 = wa verified (complete)
            $table->unsignedTinyInteger('registration_step')->default(1);

            // Account activation – false hingga step 5 selesai
            $table->boolean('is_active')->default(false);

            // OAuth info
            $table->string('oauth_provider')->nullable(); // google | apple | linkedin
            $table->string('oauth_id')->nullable();
            $table->string('oauth_token', 1024)->nullable();

            // Profile meta (diisi setelah full registration)
            $table->string('name')->nullable();
            $table->string('avatar_url')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Composite index for OAuth lookups
            $table->index(['oauth_provider', 'oauth_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
