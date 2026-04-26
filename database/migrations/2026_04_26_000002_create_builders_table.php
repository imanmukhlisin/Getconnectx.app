<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();            // 1-to-1 dengan users
            $table->string('role_category')->nullable();  // Founder, Co-Founder, Team Member
            $table->string('primary_role')->nullable();   // engineer, product, designer, dll
            $table->string('commitment_level')->nullable(); // full-time, part-time, side-project
            $table->string('work_arrangement')->nullable(); // onsite, hybrid, remote
            $table->boolean('remote_ready')->default(false);
            $table->boolean('open_to_remote')->default(false);
            $table->boolean('willing_to_relocate')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('role_category');
            $table->index('commitment_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builders');
    }
};
