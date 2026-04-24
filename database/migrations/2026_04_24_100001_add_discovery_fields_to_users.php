<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_pro')->default(false)->after('is_onboarded');
            $table->string('city')->nullable()->after('longitude');
            $table->string('country')->nullable()->after('city');
            $table->text('bio')->nullable()->after('country');
            $table->text('startup_idea')->nullable()->after('bio');
            $table->json('education')->nullable()->after('startup_idea');
            $table->json('languages')->nullable()->after('education');
            $table->string('leadership_style')->nullable()->after('languages');
            $table->string('work_arrangement')->nullable()->after('leadership_style');
            $table->boolean('remote_ready')->default(false)->after('work_arrangement');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_pro', 'city', 'country', 'bio', 'startup_idea',
                'education', 'languages', 'leadership_style',
                'work_arrangement', 'remote_ready',
            ]);
        });
    }
};
