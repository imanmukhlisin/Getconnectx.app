<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnboardingSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks for manual truncation
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        DB::table('onboarding_options')->delete();
        DB::table('onboarding_questions')->delete();
        DB::table('onboarding_transitions')->delete();
        DB::table('onboarding_steps')->delete();
        DB::table('onboarding_flows')->delete();

        // FLOW 1: COMMON (DATA DIRI) - Entry Point
        DB::table('onboarding_flows')->insert([
            'id' => 'flow_common',
            'name' => 'Data Diri',
            'is_entry' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // FLOW 2: STARTUP (Flow F)
        DB::table('onboarding_flows')->insert([
            'id' => 'flow_startup',
            'name' => 'Startup Flow',
            'is_entry' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ==========================================
        // STEPS FOR FLOW: COMMON
        // ==========================================
        
        // Step 1: Name
        DB::table('onboarding_steps')->insert([
            'id' => 'step_name',
            'flow_id' => 'flow_common',
            'order_index' => 1,
            'section' => "Let's build your general profile",
            'title' => "What's your name?",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onboarding_questions')->insert([
            ['id' => 'q_first_name', 'step_id' => 'step_name', 'order_index' => 1, 'type' => 'text', 'label' => 'First Name', 'required' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_last_name', 'step_id' => 'step_name', 'order_index' => 2, 'type' => 'text', 'label' => 'Last Name', 'required' => false, 'created_at' => now(), 'updated_at' => now()]
        ]);

        // Step 2: Role Selection (Branching happens here)
        DB::table('onboarding_steps')->insert([
            'id' => 'step_role_selection',
            'flow_id' => 'flow_common',
            'order_index' => 2,
            'section' => "Account Type",
            'title' => "How do you want to use ConnectX?",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onboarding_questions')->insert([
            ['id' => 'q_use_connectx', 'step_id' => 'step_role_selection', 'order_index' => 1, 'type' => 'single_select_card', 'label' => 'Select an option', 'required' => true, 'created_at' => now(), 'updated_at' => now()]
        ]);

        DB::table('onboarding_options')->insert([
            ['id' => 'opt_builder', 'question_id' => 'q_use_connectx', 'order_index' => 1, 'label' => "I'm a Builder", 'value' => 'builder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_startup', 'question_id' => 'q_use_connectx', 'order_index' => 2, 'label' => 'I represent a Startup', 'value' => 'startup', 'created_at' => now(), 'updated_at' => now()]
        ]);

        // Step 3: Builder Details (Linear if builder)
        DB::table('onboarding_steps')->insert([
            'id' => 'step_builder_path',
            'flow_id' => 'flow_common',
            'order_index' => 3,
            'section' => "Builder Profile",
            'title' => "What is your current focus?",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onboarding_questions')->insert([
            ['id' => 'q_builder_type', 'step_id' => 'step_builder_path', 'order_index' => 1, 'type' => 'single_select_radio', 'label' => 'Select focus', 'required' => true, 'created_at' => now(), 'updated_at' => now()]
        ]);

        DB::table('onboarding_options')->insert([
            ['id' => 'opt_founder', 'question_id' => 'q_builder_type', 'order_index' => 1, 'label' => "Founder", 'value' => 'founder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_cofounder', 'question_id' => 'q_builder_type', 'order_index' => 2, 'label' => "Co-Founder (join a startup)", 'value' => 'co_founder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_member', 'question_id' => 'q_builder_type', 'order_index' => 3, 'label' => "Team Member", 'value' => 'team_member', 'created_at' => now(), 'updated_at' => now()],
        ]);


        // ==========================================
        // STEPS FOR FLOW: STARTUP (Flow F)
        // ==========================================
        DB::table('onboarding_steps')->insert([
            'id' => 'step_startup_details',
            'flow_id' => 'flow_startup',
            'order_index' => 1,
            'section' => "Startup Profile",
            'title' => "Tell us about your startup",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('onboarding_questions')->insert([
            ['id' => 'q_startup_name', 'step_id' => 'step_startup_details', 'order_index' => 1, 'type' => 'text', 'label' => 'Startup Name', 'required' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_startup_stage', 'step_id' => 'step_startup_details', 'order_index' => 2, 'type' => 'dropdown', 'label' => 'Stage', 'required' => true, 'created_at' => now(), 'updated_at' => now()]
        ]);

        DB::table('onboarding_options')->insert([
            ['id' => 'opt_stage_idea', 'question_id' => 'q_startup_stage', 'order_index' => 1, 'label' => "Idea", 'value' => 'idea', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_stage_mvp', 'question_id' => 'q_startup_stage', 'order_index' => 2, 'label' => "MVP", 'value' => 'mvp', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // TRANSITIONS (BRANCHING LOGIC)
        // ==========================================
        
        // From Step Role Selection: If startup -> text step_startup_details (Flow Jump!)
        DB::table('onboarding_transitions')->insert([
            'from_step_id' => 'step_role_selection',
            'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'startup']),
            'to_step_id' => 'step_startup_details',
            'to_flow_id' => 'flow_startup',
            'priority' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default from side_role_selection will fall through to step_builder_path linearly 
        // since `order_index` 3 > 2 in the same flow.
    }
}
