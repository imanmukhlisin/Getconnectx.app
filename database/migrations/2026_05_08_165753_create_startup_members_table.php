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
        Schema::create('startup_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('startup_id')->constrained('startups')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_id');
            $table->decimal('equity_percent', 5, 2)->default(0);
            $table->string('commitment')->default('full_time');
            $table->string('status')->default('active'); // active, pending
            $table->timestamps();

            $table->unique(['startup_id', 'user_id']); // User can only have one member record per startup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('startup_members');
    }
};
