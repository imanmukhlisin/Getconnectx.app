# Dokumentasi Status Implementasi Backend (BE) - ConnectX

**Tanggal Update:** 20 Mei 2026  
**Fase Proyek:** Penyelesaian Sprint 4 & Persiapan Rilis Aplikasi  

---

## 📌 Ringkasan Eksekutif (Executive Summary)

Dokumen ini berisi laporan status komprehensif mengenai pencapaian, implementasi fitur, dan *blocker* pada sisi Backend (BE) dari **Sprint 1 hingga Sprint 4**. Secara keseluruhan, pondasi utama sistem backend, autentikasi, notifikasi, dan integrasi pihak ketiga (seperti RevenueCat) telah berhasil diimplementasikan. 

Akan tetapi, terdapat penyesuaian status menjadi **PENDING (Tertunda)** untuk penyelesaian fitur di Sprint 2, 3, dan 4 secara end-to-end. Hal ini bukan disebabkan oleh kegagalan sistem, melainkan karena adanya **perubahan krusial pada alur produk (Product Flow) dari sisi UI/UX & Frontend**, yaitu:
1. **Perombakan Alur Onboarding:** Alur registrasi/onboarding direduksi secara signifikan dari **14 step/halaman menjadi 9 step/halaman**.
2. **Penambahan Fitur Mode Switch:** Penambahan mode ganti peran pengguna secara dinamis pada saat proses pencarian (Discovery), yang berdampak langsung pada mekanisme antrian kartu (Discovery Cards).

Berikut adalah rincian detail per Sprint.

---

## ✅ SPRINT 1: Authentication & Core Setup 
**Status: IMPLEMENTED (Selesai)**

Fokus pada sprint ini adalah membangun pondasi keamanan, manajemen akses pengguna, dan struktur awal database aplikasi ConnectX. Seluruh target pada Sprint 1 telah berhasil diimplementasikan dengan baik.

**Daftar Fitur yang Diimplementasikan:**
- **Sistem Registrasi & Login:** Autentikasi aman menggunakan standar industri (Token-based authentication).
- **Verifikasi Email / OTP:** Alur validasi pengguna baru untuk menjaga integritas data pendaftar.
- **Manajemen Sesi (Session Management):** Pembuatan endpoint `/api/v1/auth/session` untuk melacak status sesi pengguna aktif, device tracking, dan auto-login.
- **Infrastruktur Database Utama:** Setup *migrations*, relasi dasar antar tabel (`users`, `profiles`), dan *Database Seeder* tahap awal.
- **Proteksi Endpoint (Middleware):** Pengamanan setiap jalur API internal dari akses yang tidak sah (Unauthorized Access).

---

## ⚠️ SPRINT 2: Discovery Cards, Filtering, Notifications 
**Status: SEBAGIAN IMPLEMENTED, SEBAGIAN PENDING ADAPTATION**

Sprint ini berfokus pada penyajian data profil kepada pengguna lain (Discovery), sistem pencarian terspesifikasi (Filtering), dan sistem pemberitahuan real-time (Notifications).

**Daftar Fitur yang Telah Diimplementasikan (Implemented):**
- **Sistem Notifikasi (FCM):** Arsitektur push notification yang solid menggunakan Firebase Cloud Messaging. BE telah menyiapkan template pesan dinamis (untuk matchmaking, pesan masuk, dll) serta implementasi *deep-linking payloads* yang siap di-*consume* oleh mobile app.
- **Arsitektur Dasar Filtering & Catalog:** Mengembangkan `DiscoveryCatalogService` yang dapat mengambil data filter (Industries, Skills, Roles) dari basis data master (`onboarding_options`).

**Alasan Status Menjadi PENDING:**
- **Discovery Cards Terhambat (Pending):** Algoritma penyajian kartu (Discovery Cards) membutuhkan penyesuaian ulang secara masif karena adanya **tambahan fitur Mode Switch** pada antarmuka aplikasi.
  - *Detail Kendala:* Sebelumnya, aplikasi menyajikan satu antrian linier. Dengan adanya fitur *Mode Switch*, Backend harus menulis ulang *query builder* di `FeedController` agar kartu yang disajikan, beserta opsi filternya, beradaptasi secara instan mengikuti mode yang sedang aktif dipilih pengguna (misalnya: Mode *Startup mencari Talent* vs Mode *Builder mencari Co-founder*). Logika *cache* dan *pagination* juga perlu dibangun ulang agar tidak terjadi kebocoran data antar mode.

---

## ⚠️ SPRINT 3: Matchmaking, Premium/Pro, and Unit Testing
**Status: SEBAGIAN IMPLEMENTED, SEBAGIAN PENDING ADAPTATION**

Sprint ini mencakup logika inti interaksi antar pengguna, monetisasi, dan stabilitas sistem.

**Daftar Fitur yang Telah Diimplementasikan (Implemented):**
- **Logika Inti Matchmaking:** Pembuatan API untuk aksi interaksi *Swipe Right* (Like), *Swipe Left* (Pass), dan *Super Like*. Serta deteksi latar belakang untuk kejadian *Mutual Match*.
- **Integrasi Subscription Premium/Pro (RevenueCat):** Pembuatan infrastruktur *Webhook* RevenueCat yang aman dan handal. Backend secara otomatis mensinkronisasi status langganan pengguna jika mereka membeli atau membatalkan langganan.
- **Premium Gating:** Sistem pembatasan akses (*middleware* khusus) untuk melindungi fitur berbayar seperti limitasi *Super Likes* harian dan akses ke tab profil eksklusif pengguna.
- **Unit Testing:** Penulisan *test-cases* untuk memastikan komponen kritis seperti modul Auth berjalan tanpa anomali.

**Alasan Status Menjadi PENDING:**
- **Matchmaking & Profile Completion Terhambat (Pending):** Fitur ini berstatus tertunda dikarenakan efek domino dari **perubahan besar alur Onboarding (dari 14 Step menjadi 9 Step)**.
  - *Detail Kendala:* Algoritma Matchmaking memiliki ketergantungan 100% pada bobot dan kelengkapan data hasil Onboarding. Reduksi dari 14 menjadi 9 halaman mengharuskan Backend untuk:
    1. Membuang endpoint lama dan membuat *mapping* API baru (Simplified Onboarding Endpoints).
    2. Menyesuaikan ulang field *mandatory* vs *optional* di Database.
    3. Merubah algoritma sistem penilaian profil (*Profile Completion Score*), karena parameter yang dinilai telah berubah total.

---

## 🚧 SPRINT 4: Ready for Apple Store and Playstore (No Bugs)
**Status: PENDING (Sedang Ditangguhkan Sementara)**

Sprint ini adalah fase finalisasi (*Polishing*, *Performance Optimization*, dan *Bug Squashing*) di mana aplikasi harus dipastikan bebas bug dan memenuhi standar ketat publikasi platform App Store dan Play Store.

**Alasan Status Menjadi PENDING:**
Fase penyelesaian Sprint 4 tidak mungkin dinyatakan "Ready for Store / No Bugs" pada saat ini karena:
1. Validasi "Bebas Bug" tidak dapat dilakukan sebelum integrasi API Onboarding 9-step selesai digabungkan dengan Frontend.
2. Flow Discovery Cards Mode Switch yang masih dalam tahap perombakan rawan memicu bug terkait ketidaksesuaian data (mismatched schema).
3. Pengujian akhir secara end-to-end (QA Regression) harus ditunda hingga Sprint 2 dan Sprint 3 sepenuhnya beradaptasi dengan perubahan Product Flow.

---

## 🎯 Kesimpulan & Rencana Aksi (Next Action Items)

Meskipun fondasi Backend sangat kuat (Auth, Push Notifications, Payment Webhooks sudah matang), rilis final wajib ditunda hingga *Flow Adaptation* selesai.

**Tugas Prioritas Backend Saat Ini:**
1. **Refaktor API Onboarding:** Menyelesaikan endpoints `/onboarding/status`, `/onboarding/role`, `/onboarding/preferences` untuk mengakomodasi flow 9-step secara terpusat tanpa ada *data loss*.
2. **Integrasi Mode Switch pada Discovery API:** Menyuntikkan logika kontekstual (`active_mode`) pada `DiscoveryCatalogService` agar Feed/Cards dan Filter yang direturn Backend sesuai dengan *Role* yang sedang diperankan pengguna.
3. **Database Migration & Seeding Update:** Menjalankan pembaruan *Seeder* untuk membersihkan sisa data/skema dari onboarding lama (14-step) dan memastikan integritas data untuk testing QA.

*Dokumen ini dibuat untuk menyamakan persepsi antar tim Manajemen, Frontend, Backend, dan QA mengenai status rilis aplikasi ConnectX.*
