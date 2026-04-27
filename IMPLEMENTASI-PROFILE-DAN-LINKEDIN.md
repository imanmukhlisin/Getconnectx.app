# Implementasi Sinkronisasi Profil LinkedIn via Apify Webhook

Dokumen ini merangkum arsitektur, alur sistem, dan pembaruan _database_ untuk fitur **LinkedIn Profile Synchronization**. Fitur ini memungkinkan aplikasi mengambil data profil LinkedIn pengguna (berbasis *Scraping* via Apify), menghasilkan Biografi menggunakan AI (Gemini Vertex AI), dan merekam kredensial pengguna secara asinkron tanpa terjadinya kegagalan pada antarmuka aplikasi.

---

## 1. Arsitektur Asinkron (Webhook-Driven Architecture)

Pengambilan data (_scraping_) profil LinkedIn menggunakan Apify (`dev_fusion~linkedin-profile-scraper`) umumnya memerlukan durasi antara 2 hingga 5 menit untuk _node processing_. 
Oleh karena itu, operasi tidak boleh dilakukan secara sinkron untuk menghindari _timeout_ HTTP Server dan mempertahankan performa aplikasi.

Arsitektur diubah dari pendekatan _polling_ / Sinkron (REST) menjadi **Asinkron berbasis Webhook + Job Queue**.

### Komponen Utama
1. **Pemicu (Endpoint API)**: `POST /api/v1/auth/linkedin-sync` menerima _Payload_ inisasi dari frontend dan mengembalikan status 200 secara instan.
2. **Actor Scraper (Apify)**: Berjalan secara terpisah pada jaringan _Apify Cloud_.
3. **Webhook Listener**: `POST /api/v1/webhooks/apify/linkedin` bertanggung jawab mendengarkan pesan konfirmasi keberhasilan scraping `ACTOR.RUN.SUCCEEDED` dari Apify.
4. **Queue Processor**: `ProcessLinkedInProfileJob` berjalan di memori Redis/Database Server lokal untuk mengekstraksi data, memanggil AI (Vertex AI), dan memperbaharui _Cache_.

---

## 2. Struktur Relasional Database Baru

Pembaruan skema PostgreSQL _database_ melalui _Supabase_ telah di-_deploy_ di _production_ (atau branch `develop`).

### A. Tabel `users` (Pembaruan)
Penambahan kolom referensi perangkat untuk kebutuhan notifikasi jika pekerjaan asinkron selesai:
- `last_device_id` `varchar(255) NULL`

*(Catatan: `bio` dan `position` pada tabel ini dimanfaatkan untuk menyimpan Bio yang dihasilkan AI dan Headline pengguna).*

### B. Tabel `user_credentials` (Tabel Baru)
Pemecahan entitas untuk mempermudah skalabilitas dan sentralisasi sertifikasi/riwayat pekerjaan pihak eksternal, dipisah dari tabel `users` berdasar arsitektur _Micro-service_ yang dianjurkan.

```sql
CREATE TABLE IF NOT EXISTS user_credentials (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider    VARCHAR(50) NOT NULL DEFAULT 'linkedin',
    experience  JSONB NOT NULL DEFAULT '[]'::jsonb,
    education   JSONB NOT NULL DEFAULT '[]'::jsonb,
    raw_data    JSONB,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    CONSTRAINT user_credentials_user_id_provider_unique UNIQUE (user_id, provider)
);
```

> [!CAUTION]
> **ATURAN KETAT**: Nilai _default_ untuk kolom `experience` dan `education` adalah _Array Json_ kosong (`[]`). **Dilarang memasukkan nilai `NULL`** ke dalam kolom-kolom ini. Modul *Scoring Engine (Variabel G dan J)* akan memicu _NullPointerException_ jika terjadi kesalahan pengikatan tipe JSON List. Hal ini dirancang sesuai algoritma Matchmaking ConnectX.

---

## 3. End-To-End Workflow (Alur Kerja Eksekusi)

Berikut merupakan peta proses data yang terjadi saat pengguna menekan tombol _"Sync LinkedIn"_ di aplikasi utama (Frontend Expo):

#### Fase 1: Memicu Scraper (Controller: `LinkedInSyncController`)
1. Frontend mengirim payload ke `/api/v1/auth/linkedin-sync`, berisikan `linkedin_url`, `fcm_token`, `device_id`.
2. Backend (Sanctum Auth) melakukan autentikasi `user`.
3. Meng-_update_ kolom `last_device_id` dan `fcm_token` di tabel `users`.
4. Meracik **Webhook URL Cerdas** yang dilampirkan bersama Request ke Apify:
   `https://[url]/api/v1/webhooks/apify/linkedin?user_id=[UUID]&token=[SECRET]`
5. Meneruskan _trigger_ ke API REST Apify.
6. Backend merespon langsung dalam `< 100ms` kepada Frontend dengan respon positif.

#### Fase 2: Scraping Berjalan secara Paralel 
- Apify Cloud mendaki DOM situs LinkedIn pengguna `https://www.linkedin.com/in/....` untuk menangkap variabel esensial (Headline, Foto Profil, Rentang Karir/Pendidikan, dsb). Ini butuh estimasi 3 menit.

#### Fase 3: Callback dan Konfirmasi Webhook (Controller: `ApifyWebhookController`)
1. Setelah apify selesai, Apify Server mengirim HTTP POST JSON `ACTOR.RUN.SUCCEEDED` ke URL Webhook ConnectX.
2. `ApifyWebhookController` di *Backend* mencegat *Event* tersebut.
3. Controller ini **memeriksa keamanan** dengan validasi dari Query params `&token=...`. Request yang tidak valid di-blokir (401 Unauthorized).
4. _Dataset ID Apify_ dari dalam Body Json dan _ID User_ diambil. Controller Melemparnya ke layanan *Laravel Job Queues* (`ProcessLinkedInProfileJob::dispatch()`).
5. Webhook HTTP langsung ditutup (dikembalikan dengan 200 OK ke Apify agar koneksi _Server_ terjaga).

#### Fase 4: Analisis dan Perhitungan Dataset (Job: `ProcessLinkedInProfileJob`)
1. `ProcessLinkedInProfileJob` berjalan di memori Redis / pekerja `php artisan queue:work`.
2. Job ini menarik seluruh *Item JSON* dari _Apify Dataset Cloud_.
3. Memilah `experience` (Maksimal 3 terbaru), dan `education`, diatur ke `[]` jika kosong.
4. Memicu **Integrasi Gemini 1.5 Pro (Vertex AI)** melalui metode `VertexAiService::generateLinkedInBio($headline, $experiences)`. Menggunakan *prompt system* untuk mengubah riwayat profil yang terbaca menjadi paragraf ringkas (3-4 kalimat) bergaya personal.
5. Mempebaharui data pengguna dengan _first-wins strategy_:
   * `name`
   * `avatar_url` 
   * `position` *(Digantikan dengan Headline)*
   * `bio` *(Diisi dengan teks produksi AI / Fallback jika AI limit tercapai)*
6. Memuat profil pada tabel `user_credentials` dan raw datanya dengan klausa pengamanan `updateOrCreate` untuk mencegah redudansi eksekusi.
7. Membersihkan (_Invalidate_) **Redis Caching `connectx:match_score:{user_id}...`** milik *user* tersebut agara *Matchmaking Engine* membaca _Variabel Kompatibilitas Baru_ keesokan harinya/saat discovery menu diakses di Frontend.

---

## 4. Keamanan dan Kendali Pengecualian 

*   **Penyaringan Webhook (_Webhook Filtering_)** \
    Otoritas dilindungi menggunakan `webhook_token` dari file *.env* lokal atau variabel Vercel. Webhook ini aman diekspos sebagai API Publik tanpa autentikasi _Bearer_, dikarenakan validasi Query Parameter yang rahasia mencegah simulasi data sembarang.
*   **Retry Pattern on Event Error** \
    `ProcessLinkedInProfileJob` memiliki tenggang waktu kerja (_Timeout_) sebesar **60 Detik** dengan peluang percobaaan sebanyak **2x**. Hal ini memberikan jaring penanganan jikalau API Google Vertex/Gemini AI sedang padat (_Rate Limit_ / 429).
*   **Sanitasi Null Type JSON** \
    Fungsi internal dalam Job `ProcessLinkedInProfileJob::handle()` menjamin bahwa data list JSON diproses dengan `is_array()` dan nilai primitif `[]` akan menutupi kemungkinan pengikatan Null, mematuhi prinsip pencegahan _Silent Failure Database System_. 

`--- Document End ---`

GET /api/v1/me/profile
PATCH /api/v1/me/profile
GET /api/v1/profile-options
GET /api/v1/profiles/{id}