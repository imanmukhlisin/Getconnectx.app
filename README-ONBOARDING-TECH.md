# Technical Guide: Dynamic Onboarding Engine
> Panduan ini dikhususkan untuk Backend Developer / System Owner agar paham 100% apa isi tulang punggung dari kode yang baru kita buat.

Pada dasarnya, kode yang tadi gue bikin merubah fitur *Onboarding* dari yang awalnya cuma "Update kolom profil biasa", menjadi sebuah **Sistem Kuesioner Bersyarat (Dynamic Form)**.

## 1. Arsitektur Database (7 Tabel )
Di dalam folder `database/migrations/2026_04_11_000001_create_onboarding_engine_tables.php`, akan ada 7 tabel yang saling berhubungan:

*   **`onboarding_flows` (Tabel Induk Halaman):** 
    Isinya kumpulan skenario besar. Misal Flow `Data Diri` dan Flow `Detail Startup`.
*   **`onboarding_steps` (Tabel Langkah/Halaman):** 
    Satu Flow punya banyak Step. Satu Step = 1 kali render layar Frontend. Misal: Halaman "Siapa Nama Kamu?" itu 1 Step.
*   **`onboarding_questions` (Tabel Pertanyaan):** 
    Di dalam 1 Step, bisa ada 1 atau lebih pertanyaan. Misal tanya Nama Depan (Teks) dan Nama Belakang (Teks).
*   **`onboarding_options` (Tabel Pilihan Ganda):** 
    Kalo pertanyaannya tipe *dropdown* atau *radio button* (Misal pilihan "Startup" atau "Builder"), daftarnya disimpen di sini. Punya relasi ke Questions.
*   **`onboarding_sessions` (Tabel Catatan Peserta):** 
    Begitu user login dan mulai Onboarding, sistem nyatet: "User A lagi ngerjain form nih. Posisinya sekarang lagi di layar Step nomor berapa".
*   **`onboarding_responses` (Tabel Jawaban):** 
    Tiap user kelar neken tombol "Lanjut", jawaban aslinya disimpen dalam bentuk JSON ke sini. Belom masuk ke tabel `users` utama lho!
*   **`onboarding_transitions` (Tabel Polisi Lalu Lintas):** 
    Nah ini dia tabel paling canggih. Gunanya untuk ngatur "Kalau user ngejawab A, lempar dia ke Step 5. Kalau ngejawab B, lempar putar balik ke Step 2."

## 2. Cara Kerja Si Otak: `OnboardingEngineService.php`
Service ini posisinya ada di `app/Services/`. Ini adalah nyawa dari aplikasinya.
Di dalamnya cuma ada beberapa fungsi penting:

### A. `startSession(User $user)`
Pas frontend ngebalak API Start, service ini bakal nyari `onboarding_flows` yang kolom `is_entry = true` (alias pintu gerbang masuk). Lalu dia bikinin Baris Baru di tabel `sessions`.

### B. `processAnswer($session, $stepId, $answers)`
Ini fungsi yang dipanggil tiap kali user nekan tombol "Next".
*   **Fase 1:** Dia nangkep jawaban si user, dan disimpen jepret! ke tabel `onboarding_responses`.
*   **Fase 2:** Dia mikir, "Langkah selanjutnya kemana nih?". Maka dipanggillah fungsi pengatur jalan rahasia: `determineNextStep()`.

### C. Polisi Jalanan: `determineNextStep()` & `evaluateCondition()`
Fungsi ini bakal ngecek tabel `onboarding_transitions`:
> *"Di step ini punya aturan cabang gak ya? Oh ada aturan: Kalo jawaban dari `q_use_connectx` adalah `startup`, maka alihkan (Flow Jump) ke Step ID `step_startup_details`. "*

Kalau nggak ada aturan percabangan (transisi), dia jalan linear ke angka `order_index` berikutnya. (Dari halaman 1 lurus ke halaman 2).

### D. Garis Finish: `mapResponsesToProfile()`
Akhirnya, jika fungsi `determineNextStep()` menyatakan udah nggak ada halaman lagi (Form kuesioner habis), sistem akan manggil fungsi ini.
Disinilah keajaibannya muncul: Semua file jawaban JSON dari `onboarding_responses` diolah, dibongkar, lalu dicangkokan secara nyata ke dalam kolom `name`, `startup_stage`, `role_category` dan flag `is_onboarded` bernilai `true` pada tabel `users`.

## 3. Gimana Kalo Besok Gue Mau Nambah Soal Baru?
Misal besok kita disuruh: *"Minta user ngisi URL LinkedIn dong di Onboarding!"*

Kita nggak perlu ngubah kodingan Controllers, apalagi ngubah `routes/api`. Kita bahkan nggak butuh lapor ke tim Frontend (kalau di Frontend udah ada UI tipe input url).

**Yang perlu kita lakuin cuma nambah data di Database doang!**
1. Buat record baru di tabel `onboarding_questions` dengan `id = 'q_linkedin'`, tipe `url`, dan pasangkan ke UUID Step `onboarding_steps` yang mau kita targetin.
2. Update fungsi manual `mapResponsesToProfile()` di dalam `OnboardingEngineService.php` buat nge-inject jawaban `q_linkedin` tadi ke kolom `linkedin_url` (Contoh jika kolomnya nanti kita bikin) di tabel `users`. Selesai!

Itulah serunya dan mahalnya fitur *Dynamic Engine* ini dibanding sistem statis biasa! Kita udah megang kendali form sistem setara dengan Google Form di dalam roket database kita sendiri.

### 4. Panduan Ngetes API di Postman (Live Action 6 Flow & Validasi)

> **PENGUMUMAN:** Bagian Panduan Hit API Postman ini sudah ada di file terpisah. 
> Silakan buka file **[`readme-hit-onbording.md`](./readme-hit-onbording.md)** untuk melihat panduan lengkap skenario testing, balasan JSON, dan cara nembus tembok validasi.

---------------------------------------------------------------------------------------------------------------------------------

## 5. Status Terkini & PR (Pekerjaan Rumah) Kedepannya
Berikut adalah rekap mendetail apa saja yang sudah **Rampung 100%** di Phase 1 ini, dan apa saja PR yang harus diselesaikan ke depannya:

### ✅ SUDAH Selesai (Phase 1)
Core Engine (Mesin Utamanya) sudah dibangun dan stabil untuk menopang *Dynamic Form* yang fleksibel, meliputi:
1.  **Arsitektur Database Engine:** 7 Tabel utama (*Flows, Steps, Questions, Options, Transitions, Sessions, Responses*) sudah sukses di-migrate.
2.  **API Endpoints Dinamis:** Seluruh 5 Endpoint API (`start`, `current`, `answer`, `back`, `state`) sudah dibuat dan dites berhasil menjalankan logika formulir bercabang (Branching Logic).
3.  **Hapus Profil Kaku Lama:** Penyatuan sistem; menghapus API profil lama (`stage-a-identity`, `stage-b`) dan mencabut field `entity_type` secara tuntas dari fitur Pendaftaran Registrasi (`auth/register`).
4.  **Integrasi Output ke Tabel `users`:** Ketika sesi Onboarding mencapai batas akhir form, mesin otomatis merangkum semua jawaban dan merubah flag profil utama menjadi `is_onboarded = true`.
5.  **Mekanisme Direct Upload Media (Google Cloud Storage):** Pembuatan Endpoint `/api/v1/media/upload-url` sudah berhasil dibuat. Backend tidak perlu lagi mengangkut file berat (zero payload), melainkan hanya mengeluarkan *Pre-signed URL* berumur 10 menit. File *Pitch Deck* langsung terbang dari Frontend menuju Google Cloud.

### 🚧 PR (Pekerjaan Rumah) Kedepannya

Ada beberapa elemen non-sistem (sifatnya injeksi data atau fitur sekunder luar mesin) yang harus kita catet:

**A. Penyuntikan Data Asli ke Database (Data Seeder)** *(High Priority)*
*   **Status Saat Ini:** Data Seeder yang sekarang (`OnboardingSeeder.php`) hanya berupa baris Data Dummy untuk sekadar *membuktikan jika *Branching Logic* (Founder vs Startup) berjalan baik. *(Masih banyak isian kuesioner asli yang absen.)*
*   **Actionable:** Backend wajib ngetik / nginput satu-satu sisa data pertanyaan kontrak asli ke Seeder / Database. Contohnya: *Date of Birth*, *City Dropdown*, *Gender*, *Tagging Skill Frontend/Backend/UIUX*, dll. Supaya saat Frontend manggil API, soal yang keluar udah "soal beneran".


**C. Bangun Admin Panel (Admin Dashboard)** *(Phase 2)*
*   **Konteks:** Kita butuh cara rapi buat merawat, menambah, atau menghapus form kedepannya tanpa nyentuh teks kode IDE. Apalagi jika kita harus nambah list profesi di Dropdown Skill/Talent.
*   **Actionable:** (Ditunda ke Sprint Depan), membangun Web UI (Semacam admin CMS). Di web ini ada halaman CRUD API khusus untuk melakukan "Insert/Update Data" langsung menuju tabel rahasia `onboarding_questions` dan `onboarding_options`. Sehingga manajemen data talent/startups bisa dikontrol langsung oleh Admin Aplikasi kapan saja via tombol di layar.

