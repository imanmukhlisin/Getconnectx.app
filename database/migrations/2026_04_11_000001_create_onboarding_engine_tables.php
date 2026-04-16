<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Alur Onboarding (Onboarding Flows)
        Schema::create('onboarding_flows', function (Blueprint $table) {
            $table->string('id')->primary(); // Menggunakan tipe string/VARCHAR untuk ID logis yang eksplisit, contoh: 'flow_common_data_diri'
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_entry')->default(false);
            $table->timestamps();
        });

        // 2. Tabel Langkah Onboarding (Onboarding Steps)
        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('flow_id');
            $table->foreign('flow_id')->references('id')->on('onboarding_flows')->onDelete('cascade');
            $table->integer('order_index');
            $table->string('section')->nullable();
            $table->jsonb('title');
            $table->jsonb('subtitle')->nullable();
            $table->jsonb('cta_label')->nullable();
            $table->boolean('auto_advance')->default(false);
            $table->boolean('can_go_back')->default(true);
            $table->timestamps();
        });

        // 3. Tabel Pertanyaan Onboarding (Onboarding Questions)
        Schema::create('onboarding_questions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('step_id');
            $table->foreign('step_id')->references('id')->on('onboarding_steps')->onDelete('cascade');
            $table->integer('order_index');
            $table->string('type');
            $table->jsonb('label');
            $table->jsonb('sub_label')->nullable();
            $table->jsonb('helper_text')->nullable();
            $table->jsonb('placeholder')->nullable();
            $table->boolean('required')->default(false);
            $table->jsonb('validation')->nullable();
            $table->jsonb('depends_on')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });

        // 4. Tabel Opsi Jawaban (Onboarding Options)
        Schema::create('onboarding_options', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('question_id');
            $table->foreign('question_id')->references('id')->on('onboarding_questions')->onDelete('cascade');
            $table->integer('order_index');
            $table->jsonb('label');
            $table->jsonb('sub_label')->nullable();
            $table->string('value');
            $table->string('icon')->nullable();
            $table->string('group_name')->nullable();
            $table->timestamps();
        });

        // 5. Tabel Transisi/Percabangan Onboarding (Onboarding Transitions)
        Schema::create('onboarding_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('from_step_id');
            $table->foreign('from_step_id')->references('id')->on('onboarding_steps')->onDelete('cascade');
            $table->jsonb('condition')->nullable();
            $table->string('to_step_id')->nullable();
            $table->foreign('to_step_id')->references('id')->on('onboarding_steps')->onDelete('cascade');
            $table->string('to_flow_id')->nullable();
            $table->foreign('to_flow_id')->references('id')->on('onboarding_flows')->onDelete('cascade');
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        // 6. Tabel Sesi Onboarding (Onboarding Sessions)
        Schema::create('onboarding_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('current_step_id')->nullable();
            $table->string('status')->default('in_progress'); // in_progress (sedang berjalan), completed (selesai)
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 7. Tabel Respon/Jawaban Onboarding (Onboarding Responses)
        Schema::create('onboarding_responses', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->foreign('session_id')->references('id')->on('onboarding_sessions')->onDelete('cascade');
            $table->string('step_id');
            $table->string('question_id');
            $table->jsonb('value')->nullable();
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_responses');
        Schema::dropIfExists('onboarding_sessions');
        Schema::dropIfExists('onboarding_transitions');
        Schema::dropIfExists('onboarding_options');
        Schema::dropIfExists('onboarding_questions');
        Schema::dropIfExists('onboarding_steps');
        Schema::dropIfExists('onboarding_flows');
    }
};
