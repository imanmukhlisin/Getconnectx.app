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
        if (Schema::hasColumn('users', 'entity_type')) {
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE users ALTER COLUMN entity_type DROP NOT NULL');
            } else {
                Schema::table('users', function (Blueprint $table) {
                    // Keep for non-postgres compat (e.g. mysql/sqlite)
                    $table->string('entity_type')->nullable()->change();
                });
            }
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('entity_type')->nullable();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            // Add FCM token for push notifications
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'entity_type')) {
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
                // Revert drop not null (note this fails if rows exist with null)
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE users ALTER COLUMN entity_type SET NOT NULL');
            } else {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('entity_type')->nullable(false)->change();
                });
            }
        }
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
