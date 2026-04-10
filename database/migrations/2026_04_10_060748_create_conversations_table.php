<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }
            
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
