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
        // 2. STEPS
        // ═══════════════════════════════════════════════════════
        DB::table('onboarding_steps')->insert([
            'id' => 'step_personal_dob', 'flow_id' => 'flow_common', 'order_index' => 2,
            'section' => 'Data Diri', 'title' => 'Kapan tanggal lahir Anda?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_dob', 'step_id' => 'step_personal_dob', 'order_index' => 1, 'type' => 'date', 'label' => 'Tanggal Lahir', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);

        // Langkah 3: Lokasi & Ketersediaan Remote
        DB::table('onboarding_steps')->insert([
            'id' => 'step_personal_location', 'flow_id' => 'flow_common', 'order_index' => 3,
            'section' => 'Data Diri', 'title' => 'Di mana lokasi Anda saat ini?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_location', 'step_id' => 'step_personal_location', 'order_index' => 1, 'type' => 'searchable_dropdown', 'label' => 'Pilih Kota/Negara', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_open_remote', 'step_id' => 'step_personal_location', 'order_index' => 2, 'type' => 'single_select_card', 'label' => 'Terbuka untuk remote?', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            // Pertanyaan ini akan bergantung pada jawaban q_open_remote yang disimpan di depends_on
            ['id' => 'q_remote_pref', 'step_id' => 'step_personal_location', 'order_index' => 3, 'type' => 'dropdown', 'label' => 'Preferensi Remote', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        
        // Inject atribut khusus depends_on sesuai standar Frontend (Logika form intra-halaman)
        // Preferensi hanya muncul kalo user pencet "Ya" di pertanyaan terbuka untuk remote
        DB::table('onboarding_questions')->where('id', 'q_remote_pref')->update([
            'depends_on' => json_encode(['question_id' => 'q_open_remote', 'operator' => 'equals', 'value' => 'yes'])
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_rem_yes', 'question_id' => 'q_open_remote', 'order_index' => 1, 'label' => 'Ya', 'value' => 'yes', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_rem_no', 'question_id' => 'q_open_remote', 'order_index' => 2, 'label' => 'Tidak', 'value' => 'no', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_rp_1', 'question_id' => 'q_remote_pref', 'order_index' => 1, 'label' => 'Hybrid', 'value' => 'hybrid', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_rp_2', 'question_id' => 'q_remote_pref', 'order_index' => 2, 'label' => 'Hanya Remote', 'value' => 'remote_only', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Langkah 4: Jenis Kelamin
        DB::table('onboarding_steps')->insert([
            'id' => 'step_personal_gender', 'flow_id' => 'flow_common', 'order_index' => 4,
            'section' => 'Data Diri', 'title' => 'Jenis Kelamin', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_gender', 'step_id' => 'step_personal_gender', 'order_index' => 1, 'type' => 'single_select_card', 'label' => 'Jenis Kelamin', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_gen_m', 'question_id' => 'q_gender', 'order_index' => 1, 'label' => 'Pria', 'value' => 'male', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_gen_f', 'question_id' => 'q_gender', 'order_index' => 2, 'label' => 'Wanita', 'value' => 'female', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Langkah 5: TITIK PERSIMPANGAN JALUR (Branching Point)
        // Di halaman inilah nasib routing user ditentukan berdasarkan pilihan akun mereka.
        DB::table('onboarding_steps')->insert([
            'id' => 'step_role_selection', 'flow_id' => 'flow_common', 'order_index' => 5,
            'section' => 'Tipe Akun', 'title' => 'Bagaimana Anda ingin menggunakan ConnectX?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_use_connectx', 'step_id' => 'step_role_selection', 'order_index' => 1, 'type' => 'single_select_card', 'label' => 'Pilih Tujuan Anda', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        // Demi mencegah loop redundan, pilihan builder langsung dipecah 3: Founder, Co-Founder, Team. 
        // Sedangkan startup dibiarkan misah sendiri.
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_bld_founder', 'question_id' => 'q_use_connectx', 'order_index' => 1, 'label' => "Founder", 'value' => 'founder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_bld_cofounder', 'question_id' => 'q_use_connectx', 'order_index' => 2, 'label' => "Co-Founder (Join a startup)", 'value' => 'cofounder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_bld_team', 'question_id' => 'q_use_connectx', 'order_index' => 3, 'label' => "Team Member (Join a startup)", 'value' => 'team', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_startup', 'question_id' => 'q_use_connectx', 'order_index' => 4, 'label' => "I represent a Startup", 'value' => 'startup', 'created_at' => now(), 'updated_at' => now()],
        ]);


        // ==========================================
        // 3. FLOW_BUILDER_COMMON (LANGKAH UMUM PARA TALENT)
        // ==========================================
        // Jika user memilih 3 opsi atas (Founder/Cofounder/Team), mereka wajib masuk kesini dulu.
        DB::table('onboarding_steps')->insert([
            'id' => 'step_bld_exp', 'flow_id' => 'flow_builder_common', 'order_index' => 1,
            'section' => 'Profil Builder', 'title' => 'Pengalaman startup sebelumnya?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_startup_exp', 'step_id' => 'step_bld_exp', 'order_index' => 1, 'type' => 'single_select_radio', 'label' => 'Pilih pengalaman', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_exp_1', 'question_id' => 'q_startup_exp', 'order_index' => 1, 'label' => "Pernah Membangun/Pendiri", 'value' => 'founder_exp', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_exp_2', 'question_id' => 'q_startup_exp', 'order_index' => 2, 'label' => "Pernah Bekerja di Startup", 'value' => 'employee_exp', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_exp_3', 'question_id' => 'q_startup_exp', 'order_index' => 3, 'label' => "Belum ada pengalaman", 'value' => 'no_exp', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('onboarding_steps')->insert([
            'id' => 'step_bld_industry', 'flow_id' => 'flow_builder_common', 'order_index' => 2,
            'section' => 'Minat & Ketersediaan', 'title' => 'Industri yang diminati?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            // Flow Common
            $q('q_first_name', 'step_personal_name', 1, 'text', 'Nama Depan', true, json_encode(['min_length' => 1, 'max_length' => 50])),
            $q('q_last_name', 'step_personal_name', 2, 'text', 'Nama Belakang', false),
            $q('q_dob', 'step_personal_dob', 1, 'date', 'Tanggal Lahir'),
            $q('q_location', 'step_personal_location', 1, 'searchable_dropdown', 'Pilih Kota/Negara'),
            $q('q_open_remote', 'step_personal_location', 2, 'single_select_card', 'Terbuka untuk remote?'),
            $q('q_remote_pref', 'step_personal_location', 3, 'dropdown', 'Preferensi Remote', true, null, json_encode(['question_id' => 'q_open_remote', 'operator' => 'equals', 'value' => 'yes'])),
            $q('q_gender', 'step_personal_gender', 1, 'single_select_card', 'Jenis Kelamin'),
            $q('q_use_connectx', 'step_role_selection', 1, 'single_select_card', 'Pilih Tujuan Anda'),
            // Flow Builder Common
            $q('q_startup_exp', 'step_bld_exp', 1, 'single_select_radio', 'Pilih pengalaman'),
            $q('q_industry', 'step_bld_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', true, json_encode(['max_selections' => 5])),
            $q('q_availability', 'step_bld_industry', 2, 'dropdown', 'Tingkat Komitmen'),
            $q('q_relocate', 'step_bld_industry', 3, 'single_select_card', 'Bersedia pindah domisili (Relocate)?'),
            $q('q_role_desc', 'step_bld_role', 1, 'dropdown', 'Peran Pekerjaan Utama'),
            $q('q_role_years', 'step_bld_role', 2, 'number', 'Jumlah Tahun Pengalaman'),
            $q('q_linkedin', 'step_bld_role', 3, 'url', 'URL LinkedIn', false),
            $q('q_founder_intent', 'step_founder_intent', 1, 'single_select_card', 'Tujuan Anda Merekrut'),
            // Flow A
            $q('q_flow_a_type', 'step_flow_a', 1, 'multi_select_chip', 'Tipe Karakter Co-Founder'),
            // Flow B
            $q('q_flow_b_role', 'step_flow_b', 1, 'multi_select_chip', 'Pilih Kualifikasi Pekerjaan'),
            // Flow C
            $q('q_flow_c_cf_type', 'step_flow_c', 1, 'multi_select_chip', 'Tipe Karakter Co-Founder'),
            $q('q_flow_c_tm_role', 'step_flow_c', 2, 'multi_select_chip', 'Pilih Kualifikasi Anggota Pekerjaan'),
            // Flow D
            $q('q_flow_d_type', 'step_flow_d', 1, 'single_select_card', 'Anda tipe co-founder yang seperti apa?'),
            $q('q_flow_d_equity', 'step_flow_d', 2, 'text', 'Ekspektasi Tunai vs Ekuitas (%)'),
            $q('q_flow_d_salary_type', 'step_flow_d', 3, 'dropdown', 'Jenis Gaji Minimum'),
            $q('q_flow_d_nominal', 'step_flow_d', 4, 'currency_amount', 'Mata Uang & Nominal (IDR/USD)'),
            // Flow E
            $q('q_flow_e_skill', 'step_flow_e', 1, 'multi_select_chip', 'Apa keahlian (skillset) Anda?'),
            $q('q_flow_e_equity', 'step_flow_e', 2, 'text', 'Ekspektasi Bonus/Ekuitas (Jika Ada)'),
            $q('q_flow_e_nominal', 'step_flow_e', 3, 'currency_amount', 'Gaji Minimum Spesifik'),
            // Flow F
            $q('q_ff_name', 'step_flow_f', 1, 'text', 'Nama Merk/Startup Anda'),
            $q('q_ff_stage', 'step_flow_f', 2, 'dropdown', 'Tahapan Saat Ini Berada'),
            $q('q_ff_look', 'step_flow_f', 3, 'single_select_card', 'Apa tujuan yang Anda cari di Platform Ini?'),
            $q('q_ff_ind', 'step_flow_f', 4, 'multi_select_chip', 'Sektor Industri Startup'),
            $q('q_ff_role', 'step_flow_f', 5, 'multi_select_chip', 'Tipe Co-Founder / Peran yang sedang Lowong'),
            $q('q_ff_offer', 'step_flow_f', 6, 'text', 'Sistem Penawaran (Gaji/Ekuitas)'),
        ]);

        // ═══════════════════════════════════════════════════════
        // 4. OPTIONS — Semua opsi dikumpulkan lalu di-batch insert
        // ═══════════════════════════════════════════════════════
        $o = function ($id, $qid, $order, $label, $value) use ($now) {
            return [
                'id' => $id, 'question_id' => $qid, 'order_index' => $order,
                'label' => $label, 'value' => $value,
                'created_at' => $now, 'updated_at' => $now,
            ];
        };
        $opts = [];

        // --- Location ---
        $opts[] = $o('opt_loc_1', 'q_location', 1, 'Jakarta, Indonesia', 'jakarta');
        $opts[] = $o('opt_loc_2', 'q_location', 2, 'Bandung, Indonesia', 'bandung');
        $opts[] = $o('opt_loc_3', 'q_location', 3, 'Singapore', 'singapore');

        // --- Remote ---
        $opts[] = $o('opt_rem_yes', 'q_open_remote', 1, 'Ya', 'yes');
        $opts[] = $o('opt_rem_no', 'q_open_remote', 2, 'Tidak', 'no');
        $opts[] = $o('opt_rp_1', 'q_remote_pref', 1, 'Hybrid', 'hybrid');
        $opts[] = $o('opt_rp_2', 'q_remote_pref', 2, 'Hanya Remote', 'remote_only');

        // --- Gender ---
        $opts[] = $o('opt_gen_m', 'q_gender', 1, 'Pria', 'male');
        $opts[] = $o('opt_gen_f', 'q_gender', 2, 'Wanita', 'female');

        // --- Role Selection (Tujuan ConnectX) ---
        $opts[] = $o('opt_bld_founder', 'q_use_connectx', 1, 'Founder', 'founder');
        $opts[] = $o('opt_bld_cofounder', 'q_use_connectx', 2, 'Co-Founder (Join a startup)', 'cofounder');
        $opts[] = $o('opt_bld_team', 'q_use_connectx', 3, 'Team Member (Join a startup)', 'team');
        $opts[] = $o('opt_startup', 'q_use_connectx', 4, 'I represent a Startup', 'startup');

        // --- Startup Experience ---
        $opts[] = $o('opt_exp_1', 'q_startup_exp', 1, 'Pernah Membangun/Pendiri', 'founder_exp');
        $opts[] = $o('opt_exp_2', 'q_startup_exp', 2, 'Pernah Bekerja di Startup', 'employee_exp');
        $opts[] = $o('opt_exp_3', 'q_startup_exp', 3, 'Belum ada pengalaman', 'no_exp');

        // --- Commitment Level (Professional) ---
        $opts[] = $o('opt_av_1', 'q_availability', 1, 'Full-time', 'full_time');
        $opts[] = $o('opt_av_2', 'q_availability', 2, 'Part-time', 'part_time');
        $opts[] = $o('opt_av_3', 'q_availability', 3, 'Side Project', 'side_project');
        $opts[] = $o('opt_av_4', 'q_availability', 4, 'Open to Discussion', 'open_to_discussion');

        // --- Relocate ---
        $opts[] = $o('opt_rl_1', 'q_relocate', 1, 'Ya', 'yes');
        $opts[] = $o('opt_rl_2', 'q_relocate', 2, 'Tidak', 'no');

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
            $opts[] = $o('opt_ind_' . ($i + 1), 'q_industry', $i + 1, $ind, $ind);
            $opts[] = $o('opt_ff_ind_' . ($i + 1), 'q_ff_ind', $i + 1, $ind, $ind);
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
            $opts[] = $o('opt_role_' . ($i + 1), 'q_role_desc', $i + 1, $role, $slug);
            $opts[] = $o('opt_fb_' . ($i + 1), 'q_flow_b_role', $i + 1, $role, $slug);
            $opts[] = $o('opt_fc_role_' . ($i + 1), 'q_flow_c_tm_role', $i + 1, $role, $slug);
            $opts[] = $o('opt_ff_role_' . ($i + 1), 'q_ff_role', $i + 1, $role, $slug);
        }

        // --- Founder Intent ---
        $opts[] = $o('opt_fnd_1', 'q_founder_intent', 1, 'Co-Founder', 'cofounder');
        $opts[] = $o('opt_fnd_2', 'q_founder_intent', 2, 'Anggota Tim Inti', 'team');
        $opts[] = $o('opt_fnd_3', 'q_founder_intent', 3, 'Keduanya', 'both');

        // --- Flow A: Co-Founder Type ---
        $opts[] = $o('opt_fa_1', 'q_flow_a_type', 1, 'Teknis (Hacker)', 'tech');
        $opts[] = $o('opt_fa_2', 'q_flow_a_type', 2, 'Bisnis (Hustler)', 'business');
        $opts[] = $o('opt_fa_3', 'q_flow_a_type', 3, 'Produk/Desain (Hipster)', 'product');

        // --- Flow C: Co-Founder Type ---
        $opts[] = $o('opt_fc_1', 'q_flow_c_cf_type', 1, 'Teknis', 'tech');
        $opts[] = $o('opt_fc_2', 'q_flow_c_cf_type', 2, 'Bisnis', 'business');

        // --- Flow D: Co-Founder Type + Salary ---
        $opts[] = $o('opt_fd_1', 'q_flow_d_type', 1, 'Teknis', 'tech');
        $opts[] = $o('opt_fd_2', 'q_flow_d_type', 2, 'Bisnis/Produk', 'business');
        $opts[] = $o('opt_fd_st_1', 'q_flow_d_salary_type', 1, 'Ketat (Wajib Penuh)', 'strict');
        $opts[] = $o('opt_fd_st_2', 'q_flow_d_salary_type', 2, 'Fleksibel / Tawar-menawar', 'flexible');
        $opts[] = $o('opt_fd_st_3', 'q_flow_d_salary_type', 3, 'Hanya Ekuitas (Vesting)', 'equity_only');

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
            $opts[] = $o('opt_skill_' . ($i + 1), 'q_flow_e_skill', $i + 1, $skill, $skill);
        }

        // ═══════════════════════════════════════════════════════
        // Startup Stages — Idea, MVP, Live (sesuai keputusan user)
        // ═══════════════════════════════════════════════════════
        $stages = ['Idea', 'MVP', 'Live'];
        foreach ($stages as $i => $stage) {
            $opts[] = $o('opt_stage_' . ($i + 1), 'q_ff_stage', $i + 1, $stage, strtolower($stage));
        }

        // --- Flow F: Look ---
        $opts[] = $o('opt_ff_look_1', 'q_ff_look', 1, 'Sedang Cari Co-Founder', 'cofounder');
        $opts[] = $o('opt_ff_look_2', 'q_ff_look', 2, 'Sedang Cari Anggota Tim', 'team');
        $opts[] = $o('opt_ff_look_3', 'q_ff_look', 3, 'Sedang Cari Keduanya', 'both');

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
