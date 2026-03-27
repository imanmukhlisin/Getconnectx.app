# Docker Setup - Getconnectx.app

Instruksi ini khusus untuk menjalankan aplikasi menggunakan infrastruktur Docker kustom yang telah disiapkan.

## Prasyarat
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) sudah terinstall dan berjalan.
- [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install) sudah teraktifkan (Wajib untuk Windows).

---

## Langkah Instalasi & Menjalankan

### 1. Build & Jalankan Container
Buka terminal (PowerShell) di folder proyek, lalu jalankan:
```powershell
wsl docker compose up -d --build
```
*Gunakan `wsl` di depan perintah jika Anda tidak memiliki `docker` di environment variabel PowerShell.*

### 2. Konfigurasi Aplikasi (Pertama Kali)
Jalankan perintah ini di dalam container `app`:
```powershell
# Install library PHP
wsl docker exec -it getconnectx-app composer install

# Generate App Key (Jika belum ada di .env)
wsl docker exec -it getconnectx-app php artisan key:generate

# Jalankan Migrasi Database
wsl docker exec -it getconnectx-app php artisan migrate
```

---

## Akses Layanan
- **Aplikasi:** [http://localhost](http://localhost)
- **Mailpit Dashboard:** [http://localhost:8025](http://localhost:8025)
- **Database (PostgreSQL):** `localhost:5432`

---

## Perintah Umum

| Tindakan | Perintah |
| :--- | :--- |
| Mematikan Container | `wsl docker compose down` |
| Restart Container | `wsl docker compose restart` |
| Melihat Log Aplikasi | `wsl docker compose logs -f app` |
| Masuk ke Terminal Container | `wsl docker exec -it getconnectx-app bash` |

---

## Testing
Untuk menjalankan unit test atau feature test Laravel di dalam Docker:

### 1. Jalankan Semua Test
```powershell
wsl docker exec -it getconnectx-app php artisan test
```

### 2. Jalankan Test Spesifik
```powershell
wsl docker exec -it getconnectx-app php artisan test --filter NamaTest
```

> [!TIP]
> Secara default, Laravel akan menggunakan database yang sama dengan development. Jika Anda ingin menggunakan database terpisah untuk testing (misal in-memory sqlite), atur di file `phpunit.xml`.

---

## ⚠️ Catatan Penting
- **Line Endings (LF):** Pastikan file `Dockerfile`, `.env`, dan `docker-compose.yml` menggunakan format **LF** (bukan CRLF). Jika ada error bash, jalankan `wsl dos2unix <filename>` di WSL.
- **Port 80:** Jika port 80 sudah digunakan aplikasi lain, Anda bisa mengubahnya di `docker-compose.yml` pada bagian service `web`.
