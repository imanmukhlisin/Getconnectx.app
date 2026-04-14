<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. User Preferences (Extension dari Profile)
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            // Menyimpan JSON workstyle preference seperti: {"remote": true, "flexible_hours": false}
            $table->jsonb('work_style')->nullable(); 
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // GIN Index for JSONB querying in Supabase Postgre
            // Supabase (Postgres) loves GIN indexes
            // DB::statement("CREATE INDEX user_preferences_work_style_gin ON user_preferences USING GIN (work_style)");
        });

        // Uncomment the RAW SQL GIN index since blueprint macro might not be mapped perfectly across drivers
        DB::statement('CREATE INDEX IF NOT EXISTS user_preferences_work_style_gin ON user_preferences USING GIN (work_style);');


        // 2. Likes (Swipe mechanism)
        Schema::create('likes', function (Blueprint $table) {
            $table->string('id', 40)->primary(); // e.g: like_12345
            $table->uuid('from_user_id');
            $table->uuid('to_user_id');
            $table->boolean('is_mutual')->default(false);
            $table->timestamps();

            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['from_user_id', 'to_user_id']);
            $table->index('from_user_id');
            $table->index('to_user_id');
        });

        // 3. Matches
        Schema::create('matches', function (Blueprint $table) {
            $table->string('id', 40)->primary(); // e.g: mtc_12345
            $table->uuid('user_id'); // Match initiator (A)
            $table->uuid('matched_user_id'); // Matched with (B)
            $table->string('status')->default('active'); // active, expired
            $table->timestamp('matched_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            
            // Relasi Logis langsung menunjuk sistem Chat 'conversations'
            $table->uuid('conversation_id')->nullable(); 
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('matched_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('set null');

            $table->index('user_id');
            $table->index('matched_user_id');
        });

        // 4. Match Scores
        Schema::create('match_scores', function (Blueprint $table) {
            $table->id();
            $table->string('match_id', 40);
            $table->integer('score')->default(0); // 0-100
            $table->string('label')->nullable();  // e.g: "Excellent Fit"
            $table->text('insight')->nullable();
            $table->timestamps();

            $table->foreign('match_id')->references('id')->on('matches')->onDelete('cascade');
        });

        // 5. Match Analyses (Precomputed JSON UI Ready)
        Schema::create('match_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('match_id', 40);
            $table->jsonb('analysis_json');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->foreign('match_id')->references('id')->on('matches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_analyses');
        Schema::dropIfExists('match_scores');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('user_preferences');
    }
};
