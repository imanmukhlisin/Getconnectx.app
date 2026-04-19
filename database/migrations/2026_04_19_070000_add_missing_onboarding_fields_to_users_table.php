<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('primary_role')->nullable();
            $table->integer('years_experience')->nullable();
            $table->string('startup_experience')->nullable();
            $table->string('cofounder_type')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('linkedin_data')->nullable(); // Untuk menyimpan hasil scraping Apify
            $table->string('startup_name')->nullable();
            $table->string('startup_tagline')->nullable();
            $table->boolean('open_to_remote')->default(false);
            $table->boolean('willing_to_relocate')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'gender',
                'primary_role',
                'years_experience',
                'startup_experience',
                'cofounder_type',
                'linkedin_url',
                'linkedin_data',
                'startup_name',
                'startup_tagline',
                'open_to_remote',
                'willing_to_relocate',
            ]);
        });
    }
};
