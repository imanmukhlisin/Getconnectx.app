# 🎯 ConnectX Matchmaking System (Hybrid Real-Time)

Sistem Matchmaking (Pencarian Rekan/Co-founder) ini dibangun sebagai inti (*core*) dari ConnectX. Mengacu pada instruksi desain terintegrasi, Matchmaking Engine menggunakan pola asinkronus dan berpadu langsung dengan ekosistem Chat (Supabase Realtime).

## 🚀 Fitur Utama
1. **NanoID/UUID Prefix Format:** Tabel `matches` dan `likes` menggunakan format unik (contoh: `mtc_09x21...`, `like_923...`) melalui konfigurasi standar Trait `HasPrefixedId`.
2. **Double-Blind Swipe (Tinder-like):** A me-like B (status tersimpan). Jika B me-like A, terbentuk *Mutual Match*.
3. **Queue-Based Insight Generation:** 
   Secara *Default*, saat *Match* terjadi, proses komputasi yang berat (Kalkulasi kecocokan *Skill*, *Role*, *Interest*, *Workstyle*) dipindahkan ke *Queue/Redis Worker* melalui job `GenerateMatchAnalysisJob` untuk mencegah Bottleneck pada Controller.
4. **Auto-Integration with Supabase Chatting:** Bekerjasama dengan Chat Module (`Conversation`), setiap kali Match terjadi, *Room Chat Private* langsung tercipta, siap meluncurkan notifikasi via WebSockets kapanpun.

## 🗄️ Database Optimizations (PostgreSQL)
Telah disediakan optimasi struktur data sebagai standardisasi:
- **GIN Indexing:** Indexing tipe GIN diaplikasikan pada kolom JSONB `work_style` (`user_preferences` tabel).
- **Eager Loading:** Pagination dan `With('scores')` untuk mengurangi beban N+1 Queries saat penarikan ribuan history Matches.

## 🔌 API Endpoints
Semua route berlindung di belakang otorisasi Sanctum & Onboarding middleware `registration.progress:5`.

- `POST /api/v1/matches/like` -> Melakukan Swipe Right. Payload `to_user_id`. Mengembalikan Object *It's a Match* jika status mutual.
- `GET /api/v1/matches` -> Mengambil histori Match List dan notifikasi angka `likesYouCount`.
- `GET /api/v1/matches/{matchId}/analysis` -> Membaca detail kalkulasi kecocokan JSON berstruktur untuk UI (Co-founder / Builder Analysis) yang dikerjakan oleh background Jobs.

## 🛠️ Docker Worker
**WAJIB JALAN:** Kontainer Docker `queue` otomatis disisipkan pada `docker-compose.yml`. Kontainer ini mengemban perintah (Worker daemon):
`php artisan queue:work --tries=3 --timeout=90`

Bila Anda mendeploy ini di Production (VPS / AWS), pastikan Supervisor/Redis worker terus menyala agar JSON *Analysis* ter-generate sempurna di *background*.

---

## 🧪 Step Pengujian API via Postman (Test Case)

Berikut adalah urutan (Flow) jika Anda ingin mencoba menguji apakah API Matchmaking ini berjalan mulus:

### Skenario: User A (Andi) melakukan Like ke User B (Budi)

**1. Persiapan Data (Via Database / Endpoint Profil)**
- Pastikan Anda memiliki Token (Bearer Token Sanctum) dari User A (Andi).
- Pastikan Anda mencatat User UUID milik teman Anda, yaitu User B (Budi) dari database `users` tabel.

**2. Langkah Awal: Swipe Right (Andi likes Budi)**
- **Endpoint:** `POST {{baseUrl}}/api/v1/matches/like`
- **Headers:** `Authorization: Bearer <TOKEN_USER_A>`
- **Body JSON:**
  ```json
  {
      "to_user_id": "<UUID_USER_BUDI>"
  }
  ```
- **Ekspektasi Output (HTTP 200 OK):**
  Akan mengembalikan pesan: `"message": "Like sent."` karena Budi *belum* membalas Like-nya Andi (belum Mutual).

**3. Balasan Swipe (Budi likes Andi)**
- Login kembali dan ganti Auth Token Anda menggunakan Token milik User B (Budi).
- Tembak endpoint yang *sama*, tapi kini posisinya dibalik karena Budi yang mengeksekusi terhadap Andi:
- **Endpoint:** `POST {{baseUrl}}/api/v1/matches/like`
- **Headers:** `Authorization: Bearer <TOKEN_USER_B>`
- **Body JSON:**
  ```json
  {
      "to_user_id": "<UUID_USER_ANDI>"
  }
  ```
- **Ekspektasi Output (HTTP 200 OK):**
  Boom! Akan ada balasan: `"message": "It's a Match!"`. Server juga akan mengembalikan data obyek `conversation_id` terbaru beserta ID unik *Match*-nya (`mtc_xxxxx`).

**4. Mengintip Hasil Match**
- Masih menggunakan Token (siapapun, boleh A atau B), sekarang tembak endpoint *GET*:
- **Endpoint:** `GET {{baseUrl}}/api/v1/matches`
- **Headers:** `Authorization: Bearer <TOKEN_USER_MANAPUN>`
- **Ekspektasi Output:** Anda akan melihat array Daftar teman *match* beserta properti bawaan Eager Loading (`fitSummary` score).

**5. Mengintip UI-Ready Detail JSON Analysis**
- Ambil UUID Match Anda (contoh: `mtc_83b320d0f41`) dari langkah 4 di atas.
- **Endpoint:** `GET {{baseUrl}}/api/v1/matches/mtc_83b320d0f41/analysis`
- **Ekspektasi Output:** Anda akan menerima Response berisi properti JSON *compatibilityScore*, *skillComplementarity*, dll. Kalkulasi canggih ini dihasilkan 100% secara gaib (*Background Process*) oleh kontainer *Queue Redis* Anda.
