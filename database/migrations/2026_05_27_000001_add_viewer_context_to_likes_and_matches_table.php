<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CON-72 Extension: Add viewer_context to likes & matches tables
 * so that chat lists can be properly separated by mode (talent vs startup).
 *
 * viewer_context values:
 *   - 'talent'  → User swiped while looking for a Startup/Cofounder
 *   - 'startup' → User swiped while looking for a Talent/Builder (as a Startup owner)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add viewer_context to likes (records what mode was active when the swipe happened)
        Schema::table('likes', function (Blueprint $table) {
            $table->string('viewer_context', 20)
                ->nullable()
                ->default('talent') // Backward-compat: treat all existing likes as 'talent' context
                ->after('type')
                ->comment('The mode the user was in when they swiped: talent | startup');
        });

        // Add viewer_context to matches (inherited from the like that triggered the match)
        Schema::table('matches', function (Blueprint $table) {
            $table->string('viewer_context', 20)
                ->nullable()
                ->default('talent')
                ->after('status')
                ->comment('The mode context in which this match was created: talent | startup');
        });
    }

    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropColumn('viewer_context');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('viewer_context');
        });
    }
};
