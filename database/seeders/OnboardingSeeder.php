<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnboardingSeeder extends Seeder
{
    public function run()
    {
        // -------------------------------------------------------------------------
        // MATIKAN PENGECEKAN KUNCI ASING (FOREIGN KEY) SEMENTARA
        // Ini dilakukan agar kita bisa menghapus (truncate) ulang semua data 
        // di tabel tanpa terhalang error relasi antar tabel (cascade).
        // -------------------------------------------------------------------------
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Bersihkan data lama dari bawah ke atas agar menghindari error 
        // anak-induk relasi database.
        DB::table('onboarding_options')->delete();
        DB::table('onboarding_questions')->delete();
        DB::table('onboarding_transitions')->delete();
        DB::table('onboarding_steps')->delete();
        DB::table('onboarding_flows')->delete();


        // ==========================================
        // 1. DAFTAR INDUK JALUR ONBOARDING (FLOWS)
        // ==========================================
        // Disini kita mendaftarkan 8 "Jalan Tol" yang bisa dilewati user.
        // 'is_entry' = true berarti ini adalah pintu masuk pertama untuk semua user baru.
        $flows = [
            ['id' => 'flow_common', 'name' => 'Data Diri Umum', 'is_entry' => true],
            ['id' => 'flow_builder_common', 'name' => 'Langkah Umum Builder', 'is_entry' => false],
            ['id' => 'flow_a', 'name' => 'Flow A (Founder -> Mencari Co-Founder)', 'is_entry' => false],
            ['id' => 'flow_b', 'name' => 'Flow B (Founder -> Mencari Tim)', 'is_entry' => false],
            ['id' => 'flow_c', 'name' => 'Flow C (Founder -> Mencari Keduanya)', 'is_entry' => false],
            ['id' => 'flow_d', 'name' => 'Flow D (Co-Founder yang Ingin Bergabung)', 'is_entry' => false],
            ['id' => 'flow_e', 'name' => 'Flow E (Anggota Tim yang Ingin Bergabung)', 'is_entry' => false],
            ['id' => 'flow_f', 'name' => 'Flow F (Profil Startup)', 'is_entry' => false],
        ];
        
        // Simpan kedalam database secara looping
        foreach ($flows as $f) {
            DB::table('onboarding_flows')->insert(array_merge($f, ['created_at' => now(), 'updated_at' => now()]));
        }

        // ==========================================
        // 2. FLOW_COMMON (PINTU MASUK SEMUA USER)
        // ==========================================
        // Langkah 1: Data Diri Dasar (Nama)
        DB::table('onboarding_steps')->insert([
            'id' => 'step_personal_name', 'flow_id' => 'flow_common', 'order_index' => 1,
            'section' => 'Mari bangun profil umum Anda', 'title' => 'Siapa nama Anda?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_first_name', 'step_id' => 'step_personal_name', 'order_index' => 1, 'type' => 'text', 'label' => 'Nama Depan', 'helper_text' => null, 'required' => true, 'validation' => json_encode(['min_length' => 1, 'max_length' => 50]), 'created_at' => now(), 'updated_at' => now()],
            // Perhatikan kolom validation null disini karena sifatnya opsional
            ['id' => 'q_last_name', 'step_id' => 'step_personal_name', 'order_index' => 2, 'type' => 'text', 'label' => 'Nama Belakang', 'helper_text' => 'Opsional', 'required' => false, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);

        // Langkah 2: Tanggal Lahir
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
            // Tipe soal Chip ini diatur validasi dari BE biar FE tahu max diselect cuma 5 biji.
            ['id' => 'q_industry', 'step_id' => 'step_bld_industry', 'order_index' => 1, 'type' => 'multi_select_chip', 'label' => 'Pilih Industri (Maks 5)', 'required' => true, 'validation' => json_encode(['max_selections' => 5]), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_availability', 'step_id' => 'step_bld_industry', 'order_index' => 2, 'type' => 'dropdown', 'label' => 'Ketersediaan Kerja', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_relocate', 'step_id' => 'step_bld_industry', 'order_index' => 3, 'type' => 'single_select_card', 'label' => 'Bersedia pindah domisili (Relocate)?', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_ind_1', 'question_id' => 'q_industry', 'order_index' => 1, 'label' => "Fintech", 'value' => 'fintech', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_ind_2', 'question_id' => 'q_industry', 'order_index' => 2, 'label' => "EdEdu", 'value' => 'edtech', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_av_1', 'question_id' => 'q_availability', 'order_index' => 1, 'label' => "Full-time", 'value' => 'fulltime', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_av_2', 'question_id' => 'q_availability', 'order_index' => 2, 'label' => "Part-time", 'value' => 'parttime', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_rl_1', 'question_id' => 'q_relocate', 'order_index' => 1, 'label' => "Ya", 'value' => 'yes', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_rl_2', 'question_id' => 'q_relocate', 'order_index' => 2, 'label' => "Tidak", 'value' => 'no', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('onboarding_steps')->insert([
            'id' => 'step_bld_role', 'flow_id' => 'flow_builder_common', 'order_index' => 3,
            'section' => 'Keahlian Profesional', 'title' => 'Peran apa yang menggambarkan diri Anda?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_role_desc', 'step_id' => 'step_bld_role', 'order_index' => 1, 'type' => 'dropdown', 'label' => 'Peran Pekerjaan', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_role_years', 'step_id' => 'step_bld_role', 'order_index' => 2, 'type' => 'number', 'label' => 'Jumlah Tahun Pengalaman', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_linkedin', 'step_id' => 'step_bld_role', 'order_index' => 3, 'type' => 'url', 'label' => 'URL LinkedIn', 'required' => false, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);

        // JIKA USER ADALAH FOUNDER -> BERIKUTNYA DITANYA NIAT ("Apa yang ada cari?")
        // Kenapa pertayaan cuma khusus nampang ke Founder? Karena Founder nanti masih membelah diri 
        // ke 3 jalur spesifik (Flow A, B, C) tergantung jawabannya cari apaan.
        DB::table('onboarding_steps')->insert([
            'id' => 'step_founder_intent', 'flow_id' => 'flow_builder_common', 'order_index' => 4,
            'section' => 'Tujuan Founder', 'title' => 'Apa yang Anda cari?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_founder_intent', 'step_id' => 'step_founder_intent', 'order_index' => 1, 'type' => 'single_select_card', 'label' => 'Tujuan Anda Merekrut', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_fnd_1', 'question_id' => 'q_founder_intent', 'order_index' => 1, 'label' => "Co-Founder", 'value' => 'cofounder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fnd_2', 'question_id' => 'q_founder_intent', 'order_index' => 2, 'label' => "Anggota Tim", 'value' => 'team', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fnd_3', 'question_id' => 'q_founder_intent', 'order_index' => 3, 'label' => "Keduanya", 'value' => 'both', 'created_at' => now(), 'updated_at' => now()],
        ]);


        // ==========================================
        // 4. FLOW A (Founder -> Mencari Co-Founder)
        // ==========================================
        DB::table('onboarding_steps')->insert([
            'id' => 'step_flow_a', 'flow_id' => 'flow_a', 'order_index' => 1,
            'section' => 'Mencari Rekan Founder', 'title' => 'Co-founder tipe apa yang Anda butuhkan?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_flow_a_type', 'step_id' => 'step_flow_a', 'order_index' => 1, 'type' => 'multi_select_chip', 'label' => 'Tipe Karakter Co-Founder', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_fa_1', 'question_id' => 'q_flow_a_type', 'order_index' => 1, 'label' => "Teknis", 'value' => 'tech', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fa_2', 'question_id' => 'q_flow_a_type', 'order_index' => 2, 'label' => "Bisnis", 'value' => 'business', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fa_3', 'question_id' => 'q_flow_a_type', 'order_index' => 3, 'label' => "Produk", 'value' => 'product', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // 5. FLOW B (Founder -> Mencari Tim Inti)
        // ==========================================
        DB::table('onboarding_steps')->insert([
            'id' => 'step_flow_b', 'flow_id' => 'flow_b', 'order_index' => 1,
            'section' => 'Membangun Anggota Tim', 'title' => 'Peran apa yang Anda butuhkan saat ini?', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_flow_b_role', 'step_id' => 'step_flow_b', 'order_index' => 1, 'type' => 'multi_select_chip', 'label' => 'Pilih Kualifikasi Pekerjaan', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_fb_1', 'question_id' => 'q_flow_b_role', 'order_index' => 1, 'label' => "CTO/Technical Lead", 'value' => 'cto', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fb_2', 'question_id' => 'q_flow_b_role', 'order_index' => 2, 'label' => "Product Designer", 'value' => 'designer', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fb_3', 'question_id' => 'q_flow_b_role', 'order_index' => 3, 'label' => "Growth Marketer", 'value' => 'marketer', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // 6. FLOW C (Founder -> Mencari Co-Founder & Tim Sekaligus)
        // ==========================================
        DB::table('onboarding_steps')->insert([
            'id' => 'step_flow_c', 'flow_id' => 'flow_c', 'order_index' => 1,
            'section' => 'Mencari Tim Besar', 'title' => 'Tentukan Kebutuhan Formasi Tim Pembangun', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            // Logikanya sama persis dari Flow A dan Flow B disatukan khusus form laman ini.
            ['id' => 'q_flow_c_cf_type', 'step_id' => 'step_flow_c', 'order_index' => 1, 'type' => 'multi_select_chip', 'label' => 'Tipe Karakter Co-Founder', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_flow_c_tm_role', 'step_id' => 'step_flow_c', 'order_index' => 2, 'type' => 'multi_select_chip', 'label' => 'Pilih Kualifikasi Anggota Pekerjaan', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_fc_1', 'question_id' => 'q_flow_c_cf_type', 'order_index' => 1, 'label' => "Teknis", 'value' => 'tech', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fc_2', 'question_id' => 'q_flow_c_tm_role', 'order_index' => 2, 'label' => "CTO/Technical Lead", 'value' => 'cto', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // 7. FLOW D (Co-Founder yang ingin Join Startup Lain)
        // ==========================================
        DB::table('onboarding_steps')->insert([
            'id' => 'step_flow_d', 'flow_id' => 'flow_d', 'order_index' => 1,
            'section' => 'Ingin Menjadi Co-Founder', 'title' => 'Detail Ekspektasi Bayaran Anda', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_flow_d_type', 'step_id' => 'step_flow_d', 'order_index' => 1, 'type' => 'single_select_card', 'label' => 'Anda tipe co-founder yang seperti apa?', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_flow_d_equity', 'step_id' => 'step_flow_d', 'order_index' => 2, 'type' => 'text', 'label' => 'Ekspektasi tunai/ekuitas (%)', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_flow_d_salary_type', 'step_id' => 'step_flow_d', 'order_index' => 3, 'type' => 'dropdown', 'label' => 'Jenis Gaji minimum', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_flow_d_nominal', 'step_id' => 'step_flow_d', 'order_index' => 4, 'type' => 'currency_amount', 'label' => 'Mata Uang & Nominal Kesepakatan', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_fd_1', 'question_id' => 'q_flow_d_type', 'order_index' => 1, 'label' => "Teknis", 'value' => 'tech', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fd_st_1', 'question_id' => 'q_flow_d_salary_type', 'order_index' => 1, 'label' => "Ketat (Wajib Penuh)", 'value' => 'strict', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_fd_st_2', 'question_id' => 'q_flow_d_salary_type', 'order_index' => 2, 'label' => "Fleksibel Tawar-menawar", 'value' => 'flexible', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // 8. FLOW E (Talent/Anggota Tim yang Ingin Join Startup Lain)
        // ==========================================
        DB::table('onboarding_steps')->insert([
            'id' => 'step_flow_e', 'flow_id' => 'flow_e', 'order_index' => 1,
            'section' => 'Ingin Bergabung Sebagai Tim', 'title' => 'Detail Keahlian dan Bayaran Tunjangan', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_flow_e_skill', 'step_id' => 'step_flow_e', 'order_index' => 1, 'type' => 'multi_select_chip', 'label' => 'Apa keahlian (skillset) Anda?', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_flow_e_equity', 'step_id' => 'step_flow_e', 'order_index' => 2, 'type' => 'text', 'label' => 'Ekspektasi tunai/ekuitas per bulan?', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_flow_e_nominal', 'step_id' => 'step_flow_e', 'order_index' => 3, 'type' => 'currency_amount', 'label' => 'Gaji Minimum Spesifik (Mata Uang & Nominal)', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // 9. FLOW F (Profil Pribadi Perusahaan Startup)
        // ==========================================
        // Startup TIDAK ditanya "Terbuka untuk remote?" makanya flow ini terpisah total di ujung.
        DB::table('onboarding_steps')->insert([
            'id' => 'step_flow_f', 'flow_id' => 'flow_f', 'order_index' => 1,
            'section' => 'Profil Startup', 'title' => 'Ceritakan tentang perjalanan startup Anda', 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::table('onboarding_questions')->insert([
            ['id' => 'q_ff_name', 'step_id' => 'step_flow_f', 'order_index' => 1, 'type' => 'text', 'label' => 'Nama Merk/Startup Anda', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_ff_stage', 'step_id' => 'step_flow_f', 'order_index' => 2, 'type' => 'dropdown', 'label' => 'Tahapan Saat Ini Berada', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_ff_look', 'step_id' => 'step_flow_f', 'order_index' => 3, 'type' => 'single_select_card', 'label' => 'Apa tujuan yang Anda cari di Platform Ini?', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_ff_ind', 'step_id' => 'step_flow_f', 'order_index' => 4, 'type' => 'multi_select_chip', 'label' => 'Sektor Industri Startup', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_ff_role', 'step_id' => 'step_flow_f', 'order_index' => 5, 'type' => 'multi_select_chip', 'label' => 'Tipe Co-Founder / Peran yang sedang Lowong dibutuhkan', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'q_ff_offer', 'step_id' => 'step_flow_f', 'order_index' => 6, 'type' => 'text', 'label' => 'Sistem Penawaran Bayaran tunai / Ekuitas', 'required' => true, 'validation' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('onboarding_options')->insert([
            ['id' => 'opt_ff_stg_1', 'question_id' => 'q_ff_stage', 'order_index' => 1, 'label' => "Tahap Gagasan/Ide", 'value' => 'idea', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_ff_stg_2', 'question_id' => 'q_ff_stage', 'order_index' => 2, 'label' => "Tahap Rilis Awal (MVP)", 'value' => 'mvp', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_ff_look_1', 'question_id' => 'q_ff_look', 'order_index' => 1, 'label' => "Sedang Cari Co-Founder", 'value' => 'cofounder', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'opt_ff_look_2', 'question_id' => 'q_ff_look', 'order_index' => 2, 'label' => "Sedang Cari Bawahan Tim", 'value' => 'team', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ==========================================
        // 10. TRANSITIONS (RUMUS MESIN LOGIKA OTOMATISASI BRANCHING ENGINENYA!!!)
        // ==========================================
        // Tabel transisi ini dibaca oleh Backend API "Submit Answer" ($onboardingService). 
        // Jika jawaban dari pengguna MATCH dengan kondisi, mereka diseret ke Step dan Flow Spesifik tujuan.
        $t = [];
        
        // --- ATURAN 1: BILA USER MEMILIH STARTUP ---
        // Jika jawaban pada Step 'step_role_selection' adalah Startup, seret secara paksa 
        // user tersebut dari persimpangan awal ke Halaman "step_flow_f" milik Flow F!
        $t[] = ['from_step_id' => 'step_role_selection', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'startup']), 'to_step_id' => 'step_flow_f', 'to_flow_id' => 'flow_f', 'priority' => 1, 'created_at' => now(), 'updated_at' => now()];
        
        // --- ATURAN 2: BILA USER BUKAN STARTUP (Gak ada deteksi diatas), YA BERARTI TALENT (BUILDER)! ---
        // Jika Prioritas 1 gagal kena radar (karena dia pilih Founder, Cofounder, atau tim), kita bikin Transisi Default (Null):
        // Jatuhkan dia secara paksa ke Halaman "step_bld_exp" (Pertanyaan Pengalaman Startup) milik Flow Builder Umum.
        $t[] = ['from_step_id' => 'step_role_selection', 'condition' => null, 'to_step_id' => 'step_bld_exp', 'to_flow_id' => 'flow_builder_common', 'priority' => 10, 'created_at' => now(), 'updated_at' => now()];

        // --- ATURAN 3: MEMECAH BELAH BUILDER SETELAH MELEWATI LANGKAH UMUM ---
        // Di Flow Builder Umum, Step terakhir mereka adalah nulis URL Linkedin (`step_bld_role`).
        // Disaat mereka submit Linkedin... Mesin kita tiba-tiba memeriksa *ingatan masa lalunya*:
        
        // 3.A. Kalau user dulu awal pendaftaran mencet "Founder", sodorin pertanyaan khusus intent ("Nyari Tim Atau Cofounder?").
        $t[] = ['from_step_id' => 'step_bld_role', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'founder']), 'to_step_id' => 'step_founder_intent', 'to_flow_id' => 'flow_builder_common', 'priority' => 1, 'created_at' => now(), 'updated_at' => now()];
        
        // 3.B. Kalau user cuma mencet "Co-Founder (Join a startup)", bypass!! Langsung buang ke FLOW D.
        $t[] = ['from_step_id' => 'step_bld_role', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'cofounder']), 'to_step_id' => 'step_flow_d', 'to_flow_id' => 'flow_d', 'priority' => 2, 'created_at' => now(), 'updated_at' => now()];
        
        // 3.C. Kalau user cuma mencet "Anggota biasa (Team)", bypass!! Langsung buang ke FLOW E.
        $t[] = ['from_step_id' => 'step_bld_role', 'condition' => json_encode(['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'team']), 'to_step_id' => 'step_flow_e', 'to_flow_id' => 'flow_e', 'priority' => 3, 'created_at' => now(), 'updated_at' => now()];

        // --- ATURAN 4: MEMECAH BELAH FOUNDER SAAT ITU JUGA MENJADI A, B, ATAU C ---
        // (Lanjutan skenario 3.A). Setelah founder milih mau nyari apa, di layar `step_founder_intent`. Lempar lagi!
        // Kalau nyari CF -> Terbangun Flow A
        $t[] = ['from_step_id' => 'step_founder_intent', 'condition' => json_encode(['question_id' => 'q_founder_intent', 'operator' => 'equals', 'value' => 'cofounder']), 'to_step_id' => 'step_flow_a', 'to_flow_id' => 'flow_a', 'priority' => 1, 'created_at' => now(), 'updated_at' => now()];
        // Kalau nyari Tim Biasa -> Terbangun Flow B
        $t[] = ['from_step_id' => 'step_founder_intent', 'condition' => json_encode(['question_id' => 'q_founder_intent', 'operator' => 'equals', 'value' => 'team']), 'to_step_id' => 'step_flow_b', 'to_flow_id' => 'flow_b', 'priority' => 2, 'created_at' => now(), 'updated_at' => now()];
        // Kalau nyari Keduanya Serentak -> Terbangun Flow C Gabungan
        $t[] = ['from_step_id' => 'step_founder_intent', 'condition' => json_encode(['question_id' => 'q_founder_intent', 'operator' => 'equals', 'value' => 'both']), 'to_step_id' => 'step_flow_c', 'to_flow_id' => 'flow_c', 'priority' => 3, 'created_at' => now(), 'updated_at' => now()];

        // Jalankan perintah suntik / Seed massal ke dalam memori Tabel Postgres!
        DB::table('onboarding_transitions')->insert($t);
    }
}
