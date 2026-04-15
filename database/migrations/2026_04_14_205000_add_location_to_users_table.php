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
        Schema::table('users', function (Blueprint $table) {
            // Store coordinates as decimal columns (no PostGIS dependency)
            // decimal(10,7) supports ±999.9999999 — more than enough for lat/lng
            $table->decimal('latitude', 10, 7)->nullable()->after('fcm_token');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Composite index for faster Haversine distance queries
            $table->index(['latitude', 'longitude'], 'users_location_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_location_index');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
