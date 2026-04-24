<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('stage')->nullable();              // idea, mvp, pre_seed, seed
            $table->string('industry')->nullable();
            $table->string('secondary_industry')->nullable();
            $table->integer('team_size')->default(1);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('looking_for')->nullable();          // ["Co-Founder","Team members"]
            $table->json('open_roles')->nullable();           // [{id, title}]
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('owner_id');
            $table->index('stage');
            $table->index('industry');
            $table->index(['latitude', 'longitude'], 'startups_location_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startups');
    }
};
