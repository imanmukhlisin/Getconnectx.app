# 🎯 ConnectX Matchmaking System (Hybrid Real-Time)

Sistem Matchmaking (Pencarian Rekan/Co-founder) ini dibangun sebagai inti (*core*) dari ConnectX. Mengacu pada instruksi desain terintegrasi, Matchmaking Engine menggunakan pola asinkronus dan berpadu langsung dengan ekosistem Chat (Supabase Realtime).

## 🚀 Fitur Utama
1. **NanoID/UUID Prefix Format:** Tabel `matches` dan `likes` menggunakan format unik (contoh: `mtc_09x21...`, `like_923...`) melalui konfigurasi standar Trait `HasPrefixedId`.
2. **Double-Blind Swipe (Tinder-like):** A me-like B (status tersimpan). Jika B me-like A, terbentuk *Mutual Match*. Tersedia pula aksi *Skip* untuk melewatkan kandidat.
3. **Queue-Based Insight Generation:** 
   Secara *Default*, saat *Match* terjadi, proses komputasi yang berat (Kalkulasi kecocokan *Skill*, *Role*, *Interest*, *Workstyle*) dipindahkan ke *Queue/Redis Worker* melalui job `GenerateMatchAnalysisJob` untuk mencegah Bottleneck pada Controller.
4. **Auto-Integration with Supabase Chatting:** Bekerjasama dengan Chat Module (`Conversation`), setiap kali Match terjadi, *Room Chat Private* langsung tercipta, siap meluncurkan notifikasi via WebSockets kapanpun.
5. **Discovery Feed Scoring Algorithm:** Menyeleksi kandidat berdasarkan Geo-Proximity (Haversine), Tag Overlap, dan Role Complementarity secara otomatis.

## 🗄️ Database Optimizations (PostgreSQL) & Caching
Telah disediakan optimasi struktur data sebagai standardisasi:
- **Redis Feed Caching:** Data *Feed* akan dikumpulkan ke dalam Cache (berlaku 5 menit) untuk memprioritaskan latensi rendah. Cache otomatis dibuang (*invalidated*) saat target di-swipe atau user update profil.
- **GIN Indexing:** Indexing tipe GIN diaplikasikan pada kolom JSONB `work_style` (`user_preferences` tabel).
- **Composite Indexes:** Penambahan indeks pada tabel `likes` dan `matches` mencegah N+1 loop dan *Full Table Scan* ketika aplikasi meracik tumpukan *Feed* baru tanpa kembaran.
- **Eager Loading:** Pagination dan `With('scores')` untuk mengurangi beban N+1 Queries saat penarikan ribuan history Matches.

## 🔌 API Endpoints
Semua route berlindung di belakang otorisasi Sanctum & Onboarding middleware `registration.progress:5`.

- `GET /api/v1/feed` -> Menampilkan kumpulan Feed user rekomendasi sistem.
- `POST /api/v1/swipe/connect` -> Melakukan Swipe Right. Payload `to_user_id`. Mengembalikan Object *It's a Match* jika status mutual.
- `POST /api/v1/swipe/skip` -> Melakukan Swipe Left. Target akan dibuang dari feed.
- `GET /api/v1/matches` -> Mengambil histori Match List dan notifikasi angka `likesYouCount`.
- `GET /api/v1/matches/{matchId}/analysis` -> Membaca detail kalkulasi kecocokan JSON berstruktur untuk UI (Co-founder / Builder Analysis) yang dikerjakan oleh background Jobs.

## 🛠️ Docker Worker
**WAJIB JALAN:** Kontainer Docker `queue` otomatis disisipkan pada `docker-compose.yml`. Kontainer ini mengemban perintah (Worker daemon):
`php artisan queue:work --tries=3 --timeout=90`

Bila Anda mendeploy ini di Production (VPS / AWS), pastikan Supervisor/Redis worker terus menyala agar JSON *Analysis* ter-generate sempurna di *background*.

---

## 🧪 Step Pengujian API via Postman (Test Case)

Berikut adalah urutan (Flow) jika Anda ingin mencoba menguji apakah API Matchmaking ini berjalan mulus:

### Skenario: User A (Andi) melakukan Swipe Right (Connect) ke User B (Budi)

**1. Persiapan Data (Via Database / Endpoint Feed)**
- Pastikan Anda memiliki Token (Bearer Token Sanctum) dari User A (Andi).
- Tembak `GET {{baseUrl}}/api/v1/feed` untuk melihat daftar User, catat satu UUID milik teman Anda, yaitu User B (Budi).

**2. Langkah Awal: Swipe Right (Andi Connects Budi)**
- **Endpoint:** `POST {{baseUrl}}/api/v1/swipe/connect`
- **Headers:** `Authorization: Bearer <TOKEN_USER_A>`
- **Body JSON:**
  ```json
  {
      "to_user_id": "<UUID_USER_BUDI>"
  }
  ```
- **Ekspektasi Output (HTTP 200 OK):**
  Akan mengembalikan pesan: `"message": "Connect sent."` karena Budi *belum* membalas Like-nya Andi (belum Mutual). Hasil obyeknya bernilai `"isMatch": false`.

**3. Balasan Swipe (Budi Connects Andi)**
- Login kembali dan ganti Auth Token Anda menggunakan Token milik User B (Budi).
- Tembak endpoint yang *sama*, tapi kini posisinya dibalik karena Budi yang mengeksekusi terhadap Andi:
- **Endpoint:** `POST {{baseUrl}}/api/v1/swipe/connect`
- **Headers:** `Authorization: Bearer <TOKEN_USER_B>`
- **Body JSON:**
  ```json
  {
      "to_user_id": "<UUID_USER_ANDI>"
  }
  ```
- **Ekspektasi Output (HTTP 200 OK):**
  Boom! Akan ada balasan: `"message": "It's a Match! 🎉"`. Server juga akan mengembalikan `"isMatch": true` dan `conversationId` terbaru beserta ID unik *Match*-nya (`mtc_xxxxx`).

**4. Mengintip Hasil Match**
- Masih menggunakan Token (siapapun, boleh A atau B), sekarang tembak endpoint *GET*:
- **Endpoint:** `GET {{baseUrl}}/api/v1/matches`
- **Headers:** `Authorization: Bearer <TOKEN_USER_MANAPUN>`
- **Ekspektasi Output:** Anda akan melihat array Daftar teman *match* beserta properti bawaan Eager Loading (`fitSummary` score).

**5. Mengintip UI-Ready Detail JSON Analysis**
- Ambil UUID Match Anda (contoh: `mtc_83b320d0f41`) dari langkah 4 di atas.
- **Endpoint:** `GET {{baseUrl}}/api/v1/matches/mtc_83b320d0f41/analysis`
- **Ekspektasi Output:** Anda akan menerima Response berisi properti JSON *compatibilityScore*, *skillComplementarity*, dll. Kalkulasi canggih ini dihasilkan 100% secara gaib (*Background Process*) oleh kontainer *Queue Redis* Anda.
