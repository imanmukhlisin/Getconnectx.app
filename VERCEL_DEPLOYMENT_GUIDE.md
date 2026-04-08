# Vercel Deployment Architecture & Solutions

Dokumen ini menjelaskan detail arsitektur deployment proyek ConnectX ke Vercel (Serverless). Vercel memiliki keterbatasan tersendiri karena sifatnya yang stateless dan menggunakan serverless function (AWS Lambda), sehingga diperlukan beberapa penyesuaian (hacks) khusus agar Laravel 11 dapat berjalan mulus.

> PENTING DIBACA OLEH SELURUH BACKEND TEAM: Segala macam file caching, session lokal, atau upload storage internal harus dihindari karena Vercel menggunakan Read-Only File System (EROFS).

---

## 1. File Entry Point / API Bridge (`api/index.php`)

Ini adalah file jembatan yang menghubungkan serverless function milik Vercel dengan *public root* asli Laravel (`public/index.php`).

**Perubahan / Logika:**
- **Storage Redirect ke `/tmp`**: Karena filesystem `/var/task` milik Vercel adalah Read-Only, kita membuat folder penyimpanan secara dinamis di direktori `/tmp/laravel` (satu-satunya tempat yang writable) persis sesaat sebelum Laravel *booting*.
- **Override Laravel Configs**: Memakai `putenv("APP_STORAGE_PATH")` dan `putenv("VIEW_COMPILED_PATH")` untuk memaksa *View Compiler* & Cache Laravel menggunakan `/tmp` bukan `storage`.
- **Manipulasi `$_SERVER['SCRIPT_NAME']`**: Vercel menaruh routing serverless di bawah folder `/api/` (jadi Vercel otomatis mengarahkan koneksi ke file `/api/index.php`). Di sisi lain, paket `symfony/http-foundation` yang menangani *Request HTTP* pada Laravel melihat ini dan berasumsi `/api` adalah Base Path aplikasi. Kemudian, *Base Path* ini akan dipotong secara otomatis dari Request HTTP.
  - *Efek Bencana:* Jika user membuka `https://domain.com/api/v1/auth/register`, Laravel malah menerima `v1/auth/register` secara telanjang, dan berujung mengirim JSON `404 Not Found`.
  - *Solusi Ampuh:* Kita memanipulasi _Global Variabel_ `$_SERVER['SCRIPT_NAME'] = '/index.php'` sehingga mengelabui Laravel/Symfony agar percaya bahwa script dijalankan dari ROOT aplikasi. Hasilnya? URL `/api/v1/auth/register` masuk dengan utuh.

## 2. Setting Vercel Build (`vercel.json`)

File konfigurasi utama untuk mengatur Vercel Builder.

**Perubahan / Logika:**
- `"buildCommand": "echo 'Skipping build - handled by vercel-php runtime'"`: Menghindari Vercel terpicu menjalankan Node.js atau Composer script yang tak selaras. Package dependencies akan diselesaikan secara *native* oleh `vercel-php@0.9.0`.
- `"dest": "/api/index.php"`: Meneruskan **semua URL paths** *(catch-all routing via regex)* yang bukan asset public seperti `/js` atau `/css` untuk dirujuk masuk ke API bridge kita secara otomatis.

## 3. Laravel Service Provider (`app/Providers/AppServiceProvider.php`)

Penyesuaian logic internal di sisi Laravel.

**Perubahan / Logika:**
- **Binding `path.public`**: Karena working direktori Serverless AWS Lambda agak berbeda dari localhost, kita memberi tau base path direktori 'public' agar aset dan helper `public_path()` bisa dipakai dengan benar.
- **Deteksi Environtment `/tmp`**:
  - Dulu sempat menggunakan pengecekan `is_writable(base_path('storage'))`. Sayangnya fungsi internal linux OS membaca permission `755` pada sub-folder di Vercel tapi aslinya sistem diset *Read Only* (sehingga tetap crash Error 500 karena melempar *exception* `EROFS`).
  - *Solusinya*: Kita hanya menggunakan validasi `if ($tmpStorage = getenv('APP_STORAGE_PATH'))` dan langsung menjalankan rebind ke `$this->app->useStoragePath($tmpStorage)`.

## 4. Custom PHP settings (`api/php.ini`)

- **Disable Warning Notifikasi:** Menghindari Warning *deprecated* di PHP 8.2 ke atas (terutama integrasinya terhadap PDO MySQL/PostgreSQL yang suka mengeluarkan *SSL Deprecated error*) yang dapat tercetak paksa dan merusak hasil format JSON dari Laravel API kita. Error akan dibuang ke file system error agar hanya terbaca internal dari log Vercel Function.

---

## 🛑 Action Item & Aturan Main Bagi Para Developer

Supaya tidak terjadi crash *(Error 500 / Data Hilang)* ketika API masuk Vercel:

1. **SESSION WAJIB COOKIE/DATABASE**
   Jangan gunakan storage lokal untuk autentikasi user / API token memory.
2. **JANGAN ADA `Storage::put('local')`**
   Harap hanya menggunakan Cloud/Object Storage seperti `AWS S3`, `Supabase Storage`, atau sejenisnya. Segala script upload *(terkait Koperasi / Profile Picture ConnectX)* wajib diarahkah ke external bucket.
3. **MIGRATION JANGAN LUPA DARI LOKAL**
   Karena *Database Connection* mengarah ke Supabase, kalian tidak bisa menjalankan `php artisan migrate` di Dashboard Vercel Console. Jalankan migrasi dari terminal laptop lokal salah satu programmer kalian yang menggunakan environment yang sama.
