# Simple LinkedIn Scraper

Proyek Node.js sederhana untuk menjalankan scraper profil LinkedIn menggunakan Apify API (`harvestapi/linkedin-profile-scraper`).

## Persyaratan
- Node.js (disarankan versi 18 ke atas karena script menggunakan fungsi `fetch` bawaan).

## Struktur File
- `package.json` : Konfigurasi project dan dependensi.
- `.env` : File environment yang menyimpan API token dari Apify (sudah diatur).
- `index.js` : Kode utama (main logic) untuk menjalankan scraper menggunakan endpoint Apify API.

## Cara Instalasi
1. Buka terminal di folder project ini.
2. Jalankan perintah berikut untuk menginstal package `dotenv`:
   ```bash
   npm install
   ```

## Cara Penggunaan
1. Buka file `index.js` dengan editor teks/kode pilihan kamu.
2. Temukan variabel `inputPayload`, dan ubah URL target profil LinkedIn sesuai kebutuhan:
   ```javascript
   const inputPayload = {
       "urls": [
           "https://www.linkedin.com/in/williamhgates" // Ganti dengan URL profile yang kamu inginkan
       ]
   };
   ```
3. Jalankan script scraper dengan menggunakan perintah:
   ```bash
   npm start
   ```
   Atau
   ```bash
   node index.js
   ```

## Catatan
- Script ini menggunakan mode _"Run Actor synchronously and get dataset items"_. Script akan menunggu (wait) hingga proses scraping selesai di server Apify dan kemudian langsung memberikan output hasil scrape dalam format JSON.
- Pastikan jangan menyebarkan file `.env` sembarangan karena memuat token API rahasia akun Apify kamu.
