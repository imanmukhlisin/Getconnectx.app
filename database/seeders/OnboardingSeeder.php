<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OnboardingSeeder extends Seeder
{
    public function run()
    {
        // Reset data menggunakan TRUNCATE CASCADE untuk menjamin kebersihan database
        DB::statement('TRUNCATE TABLE onboarding_flows RESTART IDENTITY CASCADE');

        $now = Carbon::now();

        // ═══════════════════════════════════════════════════════
        // 1. FLOWS
        // ═══════════════════════════════════════════════════════
        DB::table('onboarding_flows')->insert([
            ['id' => 'flow_common', 'name' => 'Data Diri Umum', 'is_entry' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_builder_common', 'name' => 'Langkah Umum Builder', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_a', 'name' => 'Flow A (Founder -> Mencari Co-Founder)', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_b', 'name' => 'Flow B (Founder -> Mencari Tim)', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_c', 'name' => 'Flow C (Founder -> Mencari Keduanya)', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_d', 'name' => 'Flow D (Co-Founder yang Ingin Bergabung)', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_e', 'name' => 'Flow E (Anggota Tim yang Ingin Bergabung)', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_f', 'name' => 'Flow F (Profil Startup)', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ═══════════════════════════════════════════════════════
        // INSERT ALL STEPS FOR ALL FLOWS (Many were missing!)
        // ═══════════════════════════════════════════════════════
        DB::table('onboarding_steps')->insert([
            // Common Flow
            ['id' => 'step_personal_name', 'flow_id' => 'flow_common', 'order_index' => 1, 'section' => 'Data Diri', 'title' => json_encode(['id' => 'Siapa nama Anda?', 'en' => 'What is your name?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_personal_dob', 'flow_id' => 'flow_common', 'order_index' => 2, 'section' => 'Data Diri', 'title' => json_encode(['id' => 'Kapan tanggal lahir Anda?', 'en' => 'When is your date of birth?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_personal_location', 'flow_id' => 'flow_common', 'order_index' => 3, 'section' => 'Data Diri', 'title' => json_encode(['id' => 'Di mana lokasi Anda saat ini?', 'en' => 'Where are you currently located?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_personal_gender', 'flow_id' => 'flow_common', 'order_index' => 4, 'section' => 'Data Diri', 'title' => json_encode(['id' => 'Jenis Kelamin', 'en' => 'Gender']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_role_selection', 'flow_id' => 'flow_common', 'order_index' => 5, 'section' => 'Tipe Akun', 'title' => json_encode(['id' => 'Bagaimana Anda ingin menggunakan ConnectX?', 'en' => 'How would you like to use ConnectX?']), 'created_at' => now(), 'updated_at' => now()],
            
            // Builder Common Flow
            ['id' => 'step_bld_exp', 'flow_id' => 'flow_builder_common', 'order_index' => 1, 'section' => 'Profil Builder', 'title' => json_encode(['id' => 'Pengalaman startup sebelumnya?', 'en' => 'Previous startup experience?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_bld_industry', 'flow_id' => 'flow_builder_common', 'order_index' => 2, 'section' => 'Minat & Ketersediaan', 'title' => json_encode(['id' => 'Industri yang diminati?', 'en' => 'Interested industries?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_bld_role', 'flow_id' => 'flow_builder_common', 'order_index' => 3, 'section' => 'Peran Pekerjaan', 'title' => json_encode(['id' => 'Apa peran/jabatan utama Anda?', 'en' => 'What is your primary role?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_founder_intent', 'flow_id' => 'flow_builder_common', 'order_index' => 4, 'section' => 'Tujuan Founder', 'title' => json_encode(['id' => 'Apa tujuan Anda merekrut di platform ini?', 'en' => 'What is your intention for recruiting on this platform?']), 'created_at' => now(), 'updated_at' => now()],
            
            // Branching Flows
            ['id' => 'step_flow_a', 'flow_id' => 'flow_a', 'order_index' => 1, 'section' => 'Mencari Co-Founder', 'title' => json_encode(['id' => 'Tipe Co-Founder yang Anda cari?', 'en' => 'What kind of Co-Founder are you looking for?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_flow_b', 'flow_id' => 'flow_b', 'order_index' => 1, 'section' => 'Mencari Tim', 'title' => json_encode(['id' => 'Kriteria Tim yang Anda cari?', 'en' => 'What team criteria are you looking for?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_flow_c', 'flow_id' => 'flow_c', 'order_index' => 1, 'section' => 'Mencari Co-Founder & Tim', 'title' => json_encode(['id' => 'Apa yang Anda cari?', 'en' => 'What are you looking for?']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_flow_d', 'flow_id' => 'flow_d', 'order_index' => 1, 'section' => 'Menjadi Co-Founder', 'title' => json_encode(['id' => 'Detail Co-Founder (Anda)', 'en' => 'Co-Founder Details (You)']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_flow_e', 'flow_id' => 'flow_e', 'order_index' => 1, 'section' => 'Menjadi Anggota Tim', 'title' => json_encode(['id' => 'Detail Keahlian (Anda)', 'en' => 'Skillset Details (You)']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'step_flow_f', 'flow_id' => 'flow_f', 'order_index' => 1, 'section' => 'Profil Startup', 'title' => json_encode(['id' => 'Mohon Lengkapi Profil Startup Anda', 'en' => 'Please Complete Your Startup Profile']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $q = function ($id, $step_id, $order, $type, $labelId, $labelEn, $required = true, $validation = null, $depends_on = null) use ($now) {
            return [
                'id' => $id, 'step_id' => $step_id, 'order_index' => $order,
                'type' => $type, 'label' => json_encode(['id' => $labelId, 'en' => $labelEn]), 'required' => $required,
                'validation' => $validation, 'depends_on' => $depends_on,
                'created_at' => $now, 'updated_at' => $now,
            ];
        };

        DB::table('onboarding_questions')->insert([
            // Flow Common
            $q('q_first_name', 'step_personal_name', 1, 'text', 'Nama Depan', 'First Name', true, json_encode(['min_length' => 1, 'max_length' => 50])),
            $q('q_last_name', 'step_personal_name', 2, 'text', 'Nama Belakang', 'Last Name', false),
            $q('q_dob', 'step_personal_dob', 1, 'date', 'Tanggal Lahir', 'Date of Birth'),
            $q('q_location', 'step_personal_location', 1, 'searchable_dropdown', 'Pilih Kota/Negara', 'Select City/Country'),
            $q('q_open_remote', 'step_personal_location', 2, 'single_select_card', 'Terbuka untuk remote?', 'Open to remote?'),
            $q('q_remote_pref', 'step_personal_location', 3, 'dropdown', 'Preferensi Remote', 'Remote Preference', true, null, json_encode(['question_id' => 'q_open_remote', 'operator' => 'equals', 'value' => 'yes'])),
            $q('q_gender', 'step_personal_gender', 1, 'single_select_card', 'Jenis Kelamin', 'Gender'),
            $q('q_use_connectx', 'step_role_selection', 1, 'single_select_card', 'Pilih Tujuan Anda', 'Choose Your Goal'),
            // Flow Builder Common
            $q('q_startup_exp', 'step_bld_exp', 1, 'single_select_radio', 'Pilih pengalaman', 'Select experience'),
            $q('q_industry', 'step_bld_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, json_encode(['max_selections' => 5])),
            $q('q_availability', 'step_bld_industry', 2, 'dropdown', 'Tingkat Komitmen', 'Commitment Level'),
            $q('q_relocate', 'step_bld_industry', 3, 'single_select_card', 'Bersedia pindah domisili (Relocate)?', 'Willing to relocate?'),
            $q('q_role_desc', 'step_bld_role', 1, 'dropdown', 'Peran Pekerjaan Utama', 'Primary Job Role'),
            $q('q_role_years', 'step_bld_role', 2, 'number', 'Jumlah Tahun Pengalaman', 'Years of Experience'),
            $q('q_linkedin', 'step_bld_role', 3, 'url', 'URL LinkedIn', 'LinkedIn URL', false),
            $q('q_founder_intent', 'step_founder_intent', 1, 'single_select_card', 'Tujuan Anda Merekrut', 'Your Recruitment Goal'),
            // Flow A
            $q('q_flow_a_type', 'step_flow_a', 1, 'multi_select_chip', 'Tipe Karakter Co-Founder', 'Co-Founder Character Type'),
            // Flow B
            $q('q_flow_b_role', 'step_flow_b', 1, 'multi_select_chip', 'Pilih Kualifikasi Pekerjaan', 'Select Job Qualifications'),
            // Flow C
            $q('q_flow_c_cf_type', 'step_flow_c', 1, 'multi_select_chip', 'Tipe Karakter Co-Founder', 'Co-Founder Character Type'),
            $q('q_flow_c_tm_role', 'step_flow_c', 2, 'multi_select_chip', 'Pilih Kualifikasi Anggota Pekerjaan', 'Select Team Qualifications'),
            // Flow D
            $q('q_flow_d_type', 'step_flow_d', 1, 'single_select_card', 'Anda tipe co-founder yang seperti apa?', 'What kind of co-founder are you?'),
            $q('q_flow_d_equity', 'step_flow_d', 2, 'text', 'Ekspektasi Tunai vs Ekuitas (%)', 'Cash vs Equity Expectation (%)'),
            $q('q_flow_d_salary_type', 'step_flow_d', 3, 'dropdown', 'Jenis Gaji Minimum', 'Minimum Salary Type'),
            $q('q_flow_d_nominal', 'step_flow_d', 4, 'currency_amount', 'Mata Uang & Nominal (IDR/USD)', 'Currency & Amount (IDR/USD)'),
            // Flow E
            $q('q_flow_e_skill', 'step_flow_e', 1, 'multi_select_chip', 'Apa keahlian (skillset) Anda?', 'What is your skillset?'),
            $q('q_flow_e_equity', 'step_flow_e', 2, 'text', 'Ekspektasi Bonus/Ekuitas (Jika Ada)', 'Bonus/Equity Expectation (If Any)'),
            $q('q_flow_e_nominal', 'step_flow_e', 3, 'currency_amount', 'Gaji Minimum Spesifik', 'Specific Minimum Salary'),
            // Flow F
            $q('q_ff_name', 'step_flow_f', 1, 'text', 'Nama Merk/Startup Anda', 'Your Brand/Startup Name'),
            $q('q_ff_stage', 'step_flow_f', 2, 'dropdown', 'Tahapan Saat Ini Berada', 'Current Stage'),
            $q('q_ff_look', 'step_flow_f', 3, 'single_select_card', 'Apa tujuan yang Anda cari di Platform Ini?', 'What goal are you looking for on this Platform?'),
            $q('q_ff_ind', 'step_flow_f', 4, 'multi_select_chip', 'Sektor Industri Startup', 'Startup Industry Sector', true, json_encode(['max_selections' => 5])),
            $q('q_ff_role', 'step_flow_f', 5, 'multi_select_chip', 'Tipe Co-Founder / Peran yang sedang Lowong', 'Co-Founder Type / Vacant Role'),
            $q('q_ff_offer', 'step_flow_f', 6, 'text', 'Sistem Penawaran (Gaji/Ekuitas)', 'Offer System (Salary/Equity)'),
        ]);

        // ═══════════════════════════════════════════════════════
        // 4. OPTIONS — Semua opsi dikumpulkan lalu di-batch insert
        // ═══════════════════════════════════════════════════════
        $o = function ($id, $qid, $order, $labelId, $labelEn, $value) use ($now) {
            return [
                'id' => $id, 'question_id' => $qid, 'order_index' => $order,
                'label' => json_encode(['id' => $labelId, 'en' => $labelEn]), 'value' => $value,
                'created_at' => $now, 'updated_at' => $now,
            ];
        };
        $opts = [];

        // --- Location ---
        $opts[] = $o('opt_loc_1', 'q_location', 1, 'Jakarta, Indonesia', 'Jakarta, Indonesia', 'jakarta');
        $opts[] = $o('opt_loc_2', 'q_location', 2, 'Bandung, Indonesia', 'Bandung, Indonesia', 'bandung');
        $opts[] = $o('opt_loc_3', 'q_location', 3, 'Singapore', 'Singapore', 'singapore');

        // --- Remote ---
        $opts[] = $o('opt_rem_yes', 'q_open_remote', 1, 'Ya', 'Yes', 'yes');
        $opts[] = $o('opt_rem_no', 'q_open_remote', 2, 'Tidak', 'No', 'no');
        $opts[] = $o('opt_rp_1', 'q_remote_pref', 1, 'Hybrid', 'Hybrid', 'hybrid');
        $opts[] = $o('opt_rp_2', 'q_remote_pref', 2, 'Hanya Remote', 'Remote Only', 'remote_only');

        // --- Gender ---
        $opts[] = $o('opt_gen_m', 'q_gender', 1, 'Pria', 'Male', 'male');
        $opts[] = $o('opt_gen_f', 'q_gender', 2, 'Wanita', 'Female', 'female');

        // --- Role Selection (Tujuan ConnectX) ---
        $opts[] = $o('opt_bld_founder', 'q_use_connectx', 1, 'Founder', 'Founder', 'founder');
        $opts[] = $o('opt_bld_cofounder', 'q_use_connectx', 2, 'Co-Founder (Bergabung ke startup)', 'Co-Founder (Join a startup)', 'cofounder');
        $opts[] = $o('opt_bld_team', 'q_use_connectx', 3, 'Anggota Tim (Bergabung ke startup)', 'Team Member (Join a startup)', 'team');
        $opts[] = $o('opt_startup', 'q_use_connectx', 4, 'Mewakili Startup (Akun Bisnis)', 'I represent a Startup', 'startup');

        // --- Startup Experience ---
        $opts[] = $o('opt_exp_1', 'q_startup_exp', 1, 'Pernah Membangun/Pendiri', 'Built/Founding Experience', 'founder_exp');
        $opts[] = $o('opt_exp_2', 'q_startup_exp', 2, 'Pernah Bekerja di Startup', 'Worked at Startup', 'employee_exp');
        $opts[] = $o('opt_exp_3', 'q_startup_exp', 3, 'Belum ada pengalaman', 'No experience', 'no_exp');

        // --- Commitment Level (Professional) ---
        $opts[] = $o('opt_av_1', 'q_availability', 1, 'Purnawaktu (Full-time)', 'Full-time', 'full_time');
        $opts[] = $o('opt_av_2', 'q_availability', 2, 'Paruh Waktu (Part-time)', 'Part-time', 'part_time');
        $opts[] = $o('opt_av_3', 'q_availability', 3, 'Proyek Sampingan', 'Side Project', 'side_project');
        $opts[] = $o('opt_av_4', 'q_availability', 4, 'Terbuka untuk Diskusi', 'Open to Discussion', 'open_to_discussion');

        // --- Relocate ---
        $opts[] = $o('opt_rl_1', 'q_relocate', 1, 'Ya', 'Yes', 'yes');
        $opts[] = $o('opt_rl_2', 'q_relocate', 2, 'Tidak', 'No', 'no');

        // ═══════════════════════════════════════════════════════
        // Industries — Sinkronisasi dengan TagSeeder (33 industri)
        // ═══════════════════════════════════════════════════════
        $industries = [
            'AI/ML', 'Fintech', 'Healthtech', 'EdTech', 'Web3', 'SaaS', 'Marketplace', 'Gaming',
            'Climate Tech', 'AgriTech', 'LegalTech', 'InsurTech', 'PropTech', 'FoodTech',
            'Logistics', 'E-Commerce', 'Media', 'Entertainment', 'Travel', 'Social', 'HRTech',
            'Cybersecurity', 'IoT', 'Robotics', 'Biotech', 'SpaceTech', 'Fashion', 'Sports',
            'Automotive', 'Energy', 'Construction', 'Telecom', 'GovTec',
        ];
        foreach ($industries as $i => $ind) {
            $opts[] = $o('opt_ind_' . ($i + 1), 'q_industry', $i + 1, $ind, $ind, $ind);
            $opts[] = $o('opt_ff_ind_' . ($i + 1), 'q_ff_ind', $i + 1, $ind, $ind, $ind);
        }

        // ═══════════════════════════════════════════════════════
        // Roles — Dipakai di q_role_desc, Flow B, Flow C, Flow F
        // ═══════════════════════════════════════════════════════
        $roles = [
            'CTO', 'CEO', 'COO', 'CMO', 'CPO', 'Product Manager', 'UI/UX Designer',
            'Backend Developer', 'Frontend Developer', 'Fullstack Developer',
            'Mobile Developer', 'Data Scientist', 'DevOps Engineer', 'Growth Marketer',
            'Content Strategist', 'Sales Executive', 'Operations Manager', 'Quality Assurance',
        ];
        $roleSlug = fn ($r) => strtolower(str_replace([' ', '/'], '_', $r));

        foreach ($roles as $i => $role) {
            $slug = $roleSlug($role);
            $opts[] = $o('opt_role_' . ($i + 1), 'q_role_desc', $i + 1, $role, $role, $slug);
            $opts[] = $o('opt_fb_' . ($i + 1), 'q_flow_b_role', $i + 1, $role, $role, $slug);
            $opts[] = $o('opt_fc_role_' . ($i + 1), 'q_flow_c_tm_role', $i + 1, $role, $role, $slug);
            $opts[] = $o('opt_ff_role_' . ($i + 1), 'q_ff_role', $i + 1, $role, $role, $slug);
        }

        // --- Founder Intent ---
        $opts[] = $o('opt_fnd_1', 'q_founder_intent', 1, 'Mencari Co-Founder', 'Looking for Co-Founder', 'cofounder');
        $opts[] = $o('opt_fnd_2', 'q_founder_intent', 2, 'Mencari Tim Inti', 'Looking for Core Team', 'team');
        $opts[] = $o('opt_fnd_3', 'q_founder_intent', 3, 'Keduanya', 'Both', 'both');

        // --- Flow A: Co-Founder Type ---
        $opts[] = $o('opt_fa_1', 'q_flow_a_type', 1, 'Teknis (Hacker)', 'Technical (Hacker)', 'tech');
        $opts[] = $o('opt_fa_2', 'q_flow_a_type', 2, 'Bisnis (Hustler)', 'Business (Hustler)', 'business');
        $opts[] = $o('opt_fa_3', 'q_flow_a_type', 3, 'Produk/Desain (Hipster)', 'Product/Design (Hipster)', 'product');

        // --- Flow C: Co-Founder Type ---
        $opts[] = $o('opt_fc_1', 'q_flow_c_cf_type', 1, 'Teknis', 'Technical', 'tech');
        $opts[] = $o('opt_fc_2', 'q_flow_c_cf_type', 2, 'Bisnis', 'Business', 'business');

        // --- Flow D: Co-Founder Type + Salary ---
        $opts[] = $o('opt_fd_1', 'q_flow_d_type', 1, 'Teknis', 'Technical', 'tech');
        $opts[] = $o('opt_fd_2', 'q_flow_d_type', 2, 'Bisnis/Produk', 'Business/Product', 'business');
        $opts[] = $o('opt_fd_st_1', 'q_flow_d_salary_type', 1, 'Ketat (Wajib Penuh)', 'Strict (Full req)', 'strict');
        $opts[] = $o('opt_fd_st_2', 'q_flow_d_salary_type', 2, 'Fleksibel / Tawar-menawar', 'Flexible / Negotiable', 'flexible');
        $opts[] = $o('opt_fd_st_3', 'q_flow_d_salary_type', 3, 'Hanya Ekuitas (Vesting)', 'Equity Only (Vesting)', 'equity_only');

        // ═══════════════════════════════════════════════════════
        // Skills — Sinkronisasi dengan TagSeeder (59 skills)
        // ═══════════════════════════════════════════════════════
        $skills = [
            'AI/ML', 'Full-Stack', 'Frontend', 'Backend', 'Mobile Dev', 'Data Science', 'Cloud/Infra',
            'DevOps', 'Blockchain', 'Cybersecurity', 'QA/Testing', 'Embedded Systems', 'Game Dev',
            'AR/VR', 'Robotics', 'NLP', 'Hardware', 'Product Management', 'UI/UX', 'Graphic Design',
            'UX Research', 'Digital Marketing', 'SEO/SEM', 'Social Media', 'Content Creation',
            'Copywriting', 'Brand Strategy', 'Email Marketing', 'Influencer Marketing', 'PR/Comms',
            'Sales Business', 'Dev Partnerships', 'Account Management', 'Customer Success',
            'Lead Generation', 'Operations', 'Supply Chain', 'Project Management', 'Strategy',
            'Process Optimization', 'Logistics', 'Finance', 'Accounting', 'Financial Modeling',
            'Fundraising', 'Investor Relations', 'Tax/Compliance', 'Bookkeeping', 'Legal',
            'HR/Recruiting', 'Talent Acquisition', 'People Ops', 'Compensation & Benefits',
            'Technical Writing', 'Community Management', 'Data Analytics', 'Market Research',
            'Public Speaking', 'Consulting',
        ];
        foreach ($skills as $i => $skill) {
            $opts[] = $o('opt_skill_' . ($i + 1), 'q_flow_e_skill', $i + 1, $skill, $skill, $skill);
        }

        // ═══════════════════════════════════════════════════════
        // Startup Stages — Idea, MVP, Live (sesuai keputusan user)
        // ═══════════════════════════════════════════════════════
        $stages = ['Idea', 'MVP', 'Live'];
        foreach ($stages as $i => $stage) {
            $opts[] = $o('opt_stage_' . ($i + 1), 'q_ff_stage', $i + 1, $stage, $stage, strtolower($stage));
        }

        // --- Flow F: Look ---
        $opts[] = $o('opt_ff_look_1', 'q_ff_look', 1, 'Sedang Cari Co-Founder', 'Looking for Co-Founder', 'cofounder');
        $opts[] = $o('opt_ff_look_2', 'q_ff_look', 2, 'Sedang Cari Anggota Tim', 'Looking for Team Members', 'team');
        $opts[] = $o('opt_ff_look_3', 'q_ff_look', 3, 'Sedang Cari Keduanya', 'Looking for Both', 'both');

        // Batch insert semua options sekaligus
        DB::table('onboarding_options')->insert($opts);

        // ═══════════════════════════════════════════════════════
        // 5. TRANSITIONS — Routing logic antar flow
        // ═══════════════════════════════════════════════════════
        DB::table('onboarding_transitions')->insert([
            // Dari role selection: startup -> Flow F, sisanya -> builder common
            ['from_step_id' => 'step_role_selection', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'startup']), 'to_step_id' => 'step_flow_f', 'to_flow_id' => 'flow_f', 'priority' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['from_step_id' => 'step_role_selection', 'condition' => null, 'to_step_id' => 'step_bld_exp', 'to_flow_id' => 'flow_builder_common', 'priority' => 10, 'created_at' => $now, 'updated_at' => $now],
            // Dari builder role: founder -> intent, cofounder -> Flow D, team -> Flow E
            ['from_step_id' => 'step_bld_role', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'founder']), 'to_step_id' => 'step_founder_intent', 'to_flow_id' => 'flow_builder_common', 'priority' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['from_step_id' => 'step_bld_role', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'cofounder']), 'to_step_id' => 'step_flow_d', 'to_flow_id' => 'flow_d', 'priority' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['from_step_id' => 'step_bld_role', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'team']), 'to_step_id' => 'step_flow_e', 'to_flow_id' => 'flow_e', 'priority' => 3, 'created_at' => $now, 'updated_at' => $now],
            // Dari founder intent: cofounder -> Flow A, team -> Flow B, both -> Flow C
            ['from_step_id' => 'step_founder_intent', 'condition' => json_encode(['question_id' => 'q_founder_intent', 'operator' => 'equals', 'value' => 'cofounder']), 'to_step_id' => 'step_flow_a', 'to_flow_id' => 'flow_a', 'priority' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['from_step_id' => 'step_founder_intent', 'condition' => json_encode(['question_id' => 'q_founder_intent', 'operator' => 'equals', 'value' => 'team']), 'to_step_id' => 'step_flow_b', 'to_flow_id' => 'flow_b', 'priority' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['from_step_id' => 'step_founder_intent', 'condition' => json_encode(['question_id' => 'q_founder_intent', 'operator' => 'equals', 'value' => 'both']), 'to_step_id' => 'step_flow_c', 'to_flow_id' => 'flow_c', 'priority' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
