# End-to-End LinkedIn Sync & Webhook Flow

Dokumentasi ini menjelaskan bagaimana integrasi antara **ConnectX**, **Apify (LinkedIn Scraper)**, dan pembacaan profil (`GET /api/v1/me/profile`) bekerja secara *end-to-end*.

---

## 1. Arsitektur & Alur Sinkronisasi (Flow)

Proses penarikan data LinkedIn dilakukan secara **Asynchronous** (di belakang layar) agar pengguna tidak perlu menunggu *loading* selama 2-3 menit saat menyelesaikan tahap Onboarding.

**Langkah-langkah Eksekusi:**
1. **Pemicu (Trigger):**
   Saat pengguna selesai menjawab Onboarding (di `OnboardingEngineService`), sistem akan mengecek apakah pengguna memasukkan URL LinkedIn.
2. **Background Dispatch:**
   Sistem menjalankan `LinkedInScraperService::triggerScrapeAsync()`. Service ini akan menembak API Apify untuk memulai Actor *LinkedIn Profile Scraper*.
3. **Penyisipan Webhook:**
   Bersamaan dengan *trigger* tersebut, sistem kita menyisipkan payload *Webhook* (`/api/v1/webhooks/apify/linkedin`). Kita memberi tahu Apify: *"Kalau kamu sudah selesai scraping, kirim datanya ke URL ini"*.
4. **Scraping Berjalan:**
   Apify menjalankan *scraping* di *cloud* mereka secara independen (memakan waktu ~2 menit).
5. **Apify Callback (Webhook Hit):**
   Apify mengirimkan HTTP POST ke endpoint webhook kita dengan membawa status keberhasilan dan `datasetId`.

---

## 2. Bagaimana Webhook Bekerja (`WebhookController`)

Ketika endpoint `POST /api/v1/webhooks/apify/linkedin` dipanggil oleh Apify, inilah yang terjadi di sisi *Backend*:

1. **Ekstrak Dataset ID:** Controller mengambil `defaultDatasetId` dari objek `resource` (sesuai spesifikasi struktur Apify terbaru).
2. **Fetch Data Scraping:** Menggunakan `datasetId` tersebut, Controller melakukan HTTP GET ke API Apify untuk mengambil isi dataset mentah (hasil profil LinkedIn yang sudah di-scrape).
3. **Pencarian User:** Controller mengekstrak URL atau *slug* profil dari dataset tersebut, lalu mencari model `User` di *database* yang memiliki *slug* `linkedin_url` identik.
4. **Simpan ke Database:**
   - **Tabel `users`**: Meng-update informasi utama seperti `name`, `avatar_url`, `position`, `bio`, `city`, `country`.
   - **Tabel `user_credentials`**: Menyimpan riwayat pendidikan (`education`), riwayat kerja (`experience`), dan menyimpan JSON mentah (`raw_data`) sebagai cadangan.

---

## 3. Bagaimana GET `/api/v1/me/profile` Membaca Data Tersebut?

API `/api/v1/me/profile` **TIDAK** memanggil Webhook atau Apify lagi. Ia hanya bertugas merender apa yang sudah disimpan secara rapi di *database* oleh proses Webhook.

Dalam kelas `ProfileResource.php`:

1. **Mapping Profil Dasar:** Atribut seperti `name`, `photoUrl`, dan `location` langsung diambil dari tabel `users` (yang mana nilainya sudah ditimpa/diperbarui oleh Webhook apabila proses *scraping* berhasil).
2. **Mapping Highlights (Experience & Education):**
   Sistem mengecek apakah relasi `credentials` ada untuk *provider* `linkedin`:
   - Jika ada, ia akan mengambil elemen **pertama** (terbaru) dari *array* `experience`. Lalu digabungkan menjadi kalimat dengan format: `"{title} at {company}"`.
   - Begitu juga dengan *array* `education` pertama, digabung menjadi: `"{degree}, {school}"`.
   - Kedua *string* ini (Jabatan Terakhir dan Pendidikan Terakhir) dimasukkan ke dalam blok `sections.highlights.items`.

Dengan demikian, ketika *Frontend* menembak `GET /api/v1/me/profile` (setelah webhook selesai), data LinkedIn yang terbaru akan otomatis muncul dalam seketika di halaman profil aplikasi tanpa proses *loading* tambahan.

---

## 4. Daftar Miss (Masalah Terdahulu) & Solusi

Selama proses pengembangan, terdapat beberapa *miss* (celah/kendala) yang sempat menghambat jalannya fitur ini:

| Kendala (Miss) | Penyebab (Root Cause) | Solusi yang Diterapkan |
| --- | --- | --- |
| **Vercel tidak men-trigger Apify (Silent Fail)** | File `LinkedInScraperService.php` belum dibuat di repositori, sehingga *job background* mengalami *Crash / Fatal Error* secara diam-diam. | Membuat *class* `LinkedInScraperService`, memasang logika Auth API Token, dan mendaftarkan URL Webhook yang benar. |
| **Webhook menerima request tapi gagal mengekstrak Dataset** | Kode sebelumnya mencari variabel `datasetId` di dalam objek `eventData`, padahal Apify versi terbaru meletakkannya di dalam objek `resource`. | Merombak logika di `WebhookController.php` agar mengekstrak variabel menggunakan `$request->input('resource.defaultDatasetId')`. |
| **User Not Found di Log Webhook** | Format `linkedin_url` yang di-input pengguna bisa sangat bervariasi (pakai `www`, tambahan bahasa `/in/`, trailing slash, dll) sehingga pencocokan *string* persis (`==`) gagal. | Menambahkan algoritma *Normalization* URL di `WebhookController`, yang mengekstrak bagian spesifik *slug* di ujung URL, lalu dicocokkan menggunakan *query* `LIKE`. |
| **Array Highlights Kosong di Profile Response** | Sesuai kontrak `CON-59`, *highlights* harus berisi data. Tapi skrip `ProfileResource` sebelumnya keliru karena menggunakan hitungan `"X+ years experience"`, yang akan gagal apabila *array* kosong. | Mengubah *mapping* di `ProfileResource.php` agar secara spesifik mengambil baris pertama dari `experience` dan `education`, lalu mengekstrak kolom jabatannya menjadi teks terstruktur. |
| **Atribut `isLinkedInSynced` Tidak Sesuai Backlog** | Variabel pendukung `isLinkedInSynced` membuat struktur JSON gagal menyesuaikan *Backlog* (*non-compliant*). | Variabel tersebut secara tegas dihilangkan demi *strict compliance* 100% terhadap dokumen *Backlog* CON-59 Front-End. |

---

## 5. Ringkasan Kesuksesan (TL;DR)

1. Apify ter-*trigger* otomatis setiap kali Onboarding selesai.
2. Webhook (`WebhookController.php`) berhasil menerima status `ACTOR.RUN.SUCCEEDED` dari Apify.
3. Webhook secara *asynchronous* mengunduh data mentah lalu memperbarui tabel `users` dan `user_credentials`.
4. Endpoint profil `/api/v1/me/profile` secara elegan dan cepat memetakan data `user_credentials` tersebut ke dalam *array* `highlights` yang dirender secara spesifik (Jabatan dan Gelar Universitas), selaras penuh dengan kontrak `CON-59`.
