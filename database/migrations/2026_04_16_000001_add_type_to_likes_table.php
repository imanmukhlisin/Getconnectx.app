<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a `type` column to the `likes` table to distinguish
     * between "connect" (swipe right) and "skip" (swipe left) actions.
     *
     * Default is 'connect' to keep backward compatibility with
     * existing records inserted before this migration.
     */
    public function up(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            // 'connect' = swipe right, 'skip' = swipe left
            $table->string('type', 10)
                  ->default('connect')
                  ->after('is_mutual')
                  ->comment('Swipe type: connect (right) or skip (left)');
        });

        // Backfill existing records — all pre-existing likes are 'connect'
        DB::statement("UPDATE likes SET type = 'connect' WHERE type IS NULL OR type = ''");

        // Index for feed exclusion queries:
        //   WHERE from_user_id = ? AND type IN ('connect', 'skip')
        DB::statement(
            'CREATE INDEX IF NOT EXISTS likes_from_type_idx ON likes (from_user_id, type)'
        );

        // Composite index for mutual-check queries:
        //   WHERE from_user_id = ? AND to_user_id = ?
        // (unique constraint already covers this, but adding explicit idx for reads)
        DB::statement(
            'CREATE INDEX IF NOT EXISTS likes_to_user_idx ON likes (to_user_id)'
        );

        // Index for feed exclusion (matches table)
        DB::statement(
            'CREATE INDEX IF NOT EXISTS matches_users_idx ON matches (user_id, matched_user_id)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS matches_users_idx');
        DB::statement('DROP INDEX IF EXISTS likes_from_type_idx');
        DB::statement('DROP INDEX IF EXISTS likes_to_user_idx');

        Schema::table('likes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
