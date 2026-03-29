# 🐳 Panduan Lengkap Docker + WSL 2 - Getconnectx.app

Dokumen ini berisi panduan operasional lengkap untuk menjalankan proyek **ConnectX** menggunakan infrastruktur Docker di lingkungan Windows dengan **WSL 2**.

---

## 🛠️ 1. Persiapan Awal (Prerequisites)
Pastikan komponen berikut sudah siap di komputer Anda:
1.  **Docker Desktop for Windows**: Pastikan "WSL 2 based engine" tercentang di Settings.
2.  **WSL 2 (Ubuntu)**: Pastikan distro Linux Anda terdaftar di Docker Desktop (Settings > Resources > WSL Integration).
3.  **VS Code + Remote Development Extension**: Sangat disarankan untuk mengedit file langsung dari dalam WSL (`\\wsl$\...`) untuk performa terbaik.

---

## 🚀 2. Cara Menjalankan (Deployment)

### Langkah A: Build & Start
Jalankan perintah ini di PowerShell pada root proyek:
```powershell
wsl docker compose up -d --build
```
*Gunakan `wsl` di awal jika Anda menjalankan dari PowerShell/CMD.*

### Langkah B: Inisialisasi Aplikasi (Satu Kali Saja)
Setelah container berjalan, jalankan ketiga perintah ini untuk sinkronisasi database dan library:
```powershell
# 1. Sinkronisasi Library PHP
wsl docker exec -it getconnectx-app composer update

# 2. Generate App Key (Jika belum ada di .env)
wsl docker exec -it getconnectx-app php artisan key:generate

# 3. Migrasi Database
wsl docker exec -it getconnectx-app php artisan migrate
```

---

## 🔗 3. Akses Layanan

| Layanan | URL / Alamat |
| :--- | :--- |
| **Aplikasi (Nginx)** | [http://localhost](http://localhost) |
| **Mailpit (Cek Email)** | [http://localhost:8025](http://localhost:8025) |
| **Database (PostgreSQL)** | `localhost:5432` |
| **User/Pass DB** | Ada di file `.env` (Default: `postgres`/`getconnectx2026`) |

---

## 💻 4. Perintah Operasional Penting

### Mengelola Container
- **Stop**: `wsl docker compose stop`
- **Start (Tanpa Rebuild)**: `wsl docker compose start`
- **Matikan & Hapus**: `wsl docker compose down`
- **Restart**: `wsl docker compose restart`

### Bekerja di Dalam Container
Jika ingin menjalankan perintah `artisan` atau `composer` lainnya:
```powershell
wsl docker exec -it getconnectx-app bash
```
Di dalam bash tersebut, Anda bisa langsung mengetik `php artisan ...` tanpa perlu `wsl docker exec` lagi.

---

## 🧪 5. Testing API (Postman)
Aplikasi ini sudah menggunakan **Laravel 11 API Scaffolding**.

### Endpoint Registrasi (Bebas Akses)
- **Base URL**: `http://localhost/api/v1`
- **POST `/auth/register`**: Daftar akun baru.

### Endpoint Login OTP (Bebas Akses)
- **POST `/auth/login/otp/send`**: Kirim OTP ke email (Body: `email`).
- **POST `/auth/login/otp/verify`**: Verifikasi OTP & dapatkan Token (Body: `email`, `otp_code`).

### Endpoint Terproteksi (Butuh Token)
- **GET `/auth/email/send-otp`**: Dapatkan OTP email (Gunakan Bearer Token).
- **GET `/api/user`**: Contoh ambil data user (Gunakan Bearer Token).

---

## ⚠️ 6. Troubleshooting & Tips (Penting!)

### A. Masalah Line Endings (CRLF vs LF)
Windows menggunakan `\r\n` sedangkan Linux (Docker) menggunakan `\n`. Jika Anda mendapat error `bash: ...: command not found`, pastikan file config (`.env`, `Dockerfile`, `docker-compose.yml`) menggunakan format **LF**.
*   **Fix via WSL**: `wsl dos2unix <nama_file>`

### B. Masalah Mount Volume / Folder Kosong
Jika folder di dalam Docker terlihat kosong, pastikan path di `docker-compose.yml` menggunakan path absolut WSL:
```yaml
volumes:
  - /mnt/d/CODEVITS/Getconnectx.app:/var/www
```

### C. Masalah Git "Unrelated Histories"
Jika saat `git pull` muncul error ini, gunakan flag:
```bash
git pull origin develop --allow-unrelated-histories
```
Atau jika terlalu banyak konflik, lakukan **Selective Checkout** seperti yang kita lakukan sebelumnya.

---

## 📦 7. Versi PHP
Saat ini proyek dikonfigurasi menggunakan **PHP 8.2-FPM**. Jika ingin mengganti versi:
1. Ubah `FROM php:X.X-fpm` di file `Dockerfile`.
2. Hapus container lama: `wsl docker compose down`.
3. Build ulang: `wsl docker compose up -d --build`.
