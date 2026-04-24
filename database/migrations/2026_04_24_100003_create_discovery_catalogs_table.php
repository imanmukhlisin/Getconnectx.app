<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_catalogs', function (Blueprint $table) {
            $table->string('id', 60)->primary();           // e.g. "ind_ai", "skill_react"
            $table->string('type', 30);                     // industry, skill, role, language
            $table->string('group_id', 60);                 // e.g. "grp_industry_core_technology"
            $table->string('group_label');                   // e.g. "Core Technology"
            $table->string('label');                         // e.g. "AI"
            $table->json('modes');                           // ["finding_cofounder","building_team"]
            $table->boolean('is_premium')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'group_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_catalogs');
    }
};
