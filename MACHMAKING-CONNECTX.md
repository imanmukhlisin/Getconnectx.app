# 🎯 ConnectX Matchmaking & Discovery Engine

Dokumen ini adalah ringkasan final komprehensif terkait segala modul pencarian (*Matchmaking*) pada platform ConnectX, menyatukan versi Legacy (V1) dengan fitur Filter Premium (V2 Discovery) ke dalam arsitektur terkini.

---

## 🏛️ Arsitektur Utama

Platform ConnectX menggunakan model **Hybrid Matchmaking**:
1. **Passive Feed (V1)**: Menampilkan rekomendasi kandidat secara otomatis menggunakan asinkronus (Redis Caching & Queue).
2. **Active Discovery (V2)**: Memungkinkan pengguna mencari secara spesifik dengan sistem kriteria filter hirarkis menggunakan algoritma Geolocation (Haversine Formula Math) dan Eloquent dynamic querying.

Sistem matchmaking bekerja ganda, menangani koneksi antar profesional (**P2P** / Co-Founder match) maupun antar profesional dengan institusi startup (**P2B** / Hire/Join match).

### 🤖 Layer Komputasi & AI

Semua operasi logika kalkulasi compatibility dijalankan di Backend (*Backend-Owned Logic*):
1. **The Math (Layer 3 Scoring):**
   * **Free User**: Kecocokan dihitung dari 4 bobot (Role Mode Fit, Kelengkapan Sikill (Hacker x Hustler), Kesamaan Industri via Jaccard-Similarity, dan Level Komitmen).
   * **Premium User (Pro)**: Menganalisa parameter tambahan (Lokasi / Kesediaan Relokasi, Stage Startup, Pengalaman, Kepemimpinan, dll).
   * **Absolute Cap Limit:** Sekuat apapun variabel koneksinya, skor secara logis dan absolut dikunci maksimal pada persentase **100%**.
   
2. **Vertex AI Insight:**
   Endpoint `/discovery/cards` ditembak sekaligus dengan prompt ke **Google Cloud Vertex AI (Gemini 1.5 Pro)** untuk membaca maksud filter user dan kondisi profilnya secara dinamis. Hasil *personalized insight* ini di-cache di Redis (TTL 24 jam) untuk menghemat biaya Google API.

3. **Background Analysis (Async):**
   Ketika dua koneksi berujung pada status `[It's a Match!]`, penjabaran spesifik (detail compatibility JSON seperti poin Plus dan Minus sinergi mereka) dikomputasikan di Database Queue (`GenerateMatchAnalysisJob`), membelah beban traffic controller utama.

---

## 👑 Otorisasi & Alur Premium (ConnectX Pro)

Filter Engine dirancang untuk **memblokir penuh (Hard-Block)** pengguna gratisan (Non-Pro) yang mencoba melampirkan key/parameter milik fitur eksklusif (contoh: *AI Match Precision Limit* atau *Startup Stage Criteria* opsional).
Aplikasi merespons secara ketat dengan status Code `403 PREMIUM_REQUIRED` jika akses terlarang.

Termasuk dalam langganan premium adalah hak melakukan **Last Swipe Rewind** (Pembalasan aksi Like/Pass terakhir), yang dikomunikasikan secara konsisten antara Feed dan History di dalam database. Batasan berlaku jika tindakan terdahulu adalah *Mutual Match*, otomatis tidak dapat dimundurkan (`ALREADY_MATCHED`).

---

## 🏗️ Pola Database & Performa

*   **Penyimpanan Discovery Master Data:** Data industri, skill, dan bahasa diurus secara sentral pada seeders dan tabel `discovery_catalogs` agar tidak ada deviasi pengetikan (*Typo*) yang merusak kalkulasi matematis.
*   **Cursor Pagination (`nextCursor`):** Engine menghindari teknik offset limit klasik. Tumpukan jutaan Card secara dinamis di-loop dan dipaginate merujuk ke ID Object sebelumnya via algoritma kursor untuk menjamin zero-latency scroll pada frontend.
*   **Haversine Math Logic:** Filter tipe `distanceKm` berjalan di atas rumus spherical latitude/longitude, sehingga meniadakan keharusan memasang engine PostGIS, dan aplikasi tetap berjalan luwes menggunakan database PostgreSQL konvensional di Supabase.

---
*Untuk panduan detail API dan testing endpoints, silakan merujuk pada dokumen `API-MACHMAKING.md`.*
