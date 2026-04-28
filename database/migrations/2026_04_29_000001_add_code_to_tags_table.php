<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // Kolom code untuk lookup stabil (misal: ph_1, ph_2, sk_1, sk_2)
            // nullable agar tag lama (industry/skill) tidak terpengaruh
            $table->string('code')->nullable()->unique()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
