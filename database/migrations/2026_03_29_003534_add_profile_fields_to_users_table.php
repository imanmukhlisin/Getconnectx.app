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
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('position')->nullable()->after('username');
            $table->string('role_category')->nullable()->after('position');
            $table->string('commitment_level')->nullable()->after('role_category');
            $table->string('startup_stage')->nullable()->after('commitment_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'position',
                'role_category',
                'commitment_level',
                'startup_stage'
            ]);
        });
    }
};
