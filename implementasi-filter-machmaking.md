# 🤖 CLAUDE INSTRUCTION: ConnectX Discovery Filter API (V2 Contract)
**Target:** Implementasi Discovery Filter Engine (Layer 2), Validasi Premium, dan Integrasi AI via GCS (Laravel 11, PostgreSQL, Redis).
**Context:** Anda adalah Senior Backend Developer. Tugas utama Anda saat ini adalah membangun sistem *Discovery API* yang mematuhi **V2 API Contract** dari Frontend secara mutlak. 
*Catatan Penting:* Algoritma skor matematika kompleks (Layer 3) sedang menunggu persetujuan Product Manager. Untuk saat ini, buat sistem *Filtering* (Lapis 2) yang sempurna dengan skor sementara (*dummy score*).

## ⚠️ STRICT RULES (DO NOT IGNORE)
1. **API CONTRACT COMPLIANCE:** Anda WAJIB mematuhi format JSON Request/Response, *Routing*, *Pagination* (Cursor), dan *Error Codes* di bawah ini 100%.
2. **PREMIUM VALIDATION:** Frontend akan tetap mengirimkan parameter filter premium. Jika `is_pro = false`, Anda WAJIB memblokir request dan mengembalikan JSON error spesifik.
3. **GCS AI INTEGRATION:** Integrasi AI (Gemini) WAJIB menggunakan `service_account.json` milik Google Cloud (Vertex AI), BUKAN API Key standar.
4. **SKIP COMPLEX MATH:** Jangan implementasikan rumus algoritma kompleks. Fokus pada validasi struktur *Nested JSON Filter* menjadi klausa `WHERE/WHERE IN` menggunakan *Eloquent/Query Builder*.

---

## 🧠 MODULE 1: AI INTEGRATION (GCS VERTEX AI)
Aplikasi menggunakan ekosistem Google Cloud:
`GOOGLE_APPLICATION_CREDENTIALS=/path/to/service_account.json`
`GCS_CLIENT_ID=...` `GCS_SECRET=...` `GCS_URI=...`

**Tugas Anda:**
1. Buat Service Class khusus untuk Vertex AI yang membaca `service_account.json`.
2. Sementara ini, gunakan AI hanya untuk men-generate nilai `ai_insight_text` statis/sederhana berdasarkan data yang di-filter.
3. Cache hasil AI di Redis (`Redis::setex`) agar API Google tidak dipanggil berulang kali.

---

## 🎛️ MODULE 2: DISCOVERY FILTER ENGINE (LAYER 2)
Frontend mengirimkan filter dalam bentuk *POST Body* berstruktur (Nested JSON). Tangkap request dari `POST /api/v1/discovery/cards` dan terjemahkan ke *Query Builder*.

**A. FILTER DASAR (Free & Pro User):**
*   `context.mode`: Tentukan arah query. 
    *   Jika `finding_cofounder` / `building_team` -> Query ke tabel `users` (`entityType: "profile"`).
    *   Jika `explore_startups` / `joining_startups` -> Query ke tabel `startups` (`entityType: "startup"`).
*   `filters.industryIds`, `filters.skillIds`, `filters.roleNeededIds` -> Gunakan klausa `WHERE IN`.
*   `filters.locationAvailability`: Ambil properti `latitude`, `longitude`, dan `distanceKm` untuk filter radius spasial menggunakan PostGIS/Haversine.

**B. FILTER PREMIUM & ERROR HANDLING:**
Frontend mungkin mengirim objek premium seperti `aiMatchPrecision` (berisi `minimumMatchScore`), `founderBuilderQuality`, `executionQuality`, atau `globalCompatibility`.
*   **Aturan:** Lakukan pengecekan `$user->is_pro`. Jika bernilai `false` namun objek filter premium ini ada di dalam *request body*, Anda WAJIB menolak request dengan balasan HTTP 403 / 400 dan JSON berikut:
    ```json
    {
      "success": false,
      "message": "Premium subscription required to use advanced discovery filters",
      "error": { "code": "PREMIUM_REQUIRED" }
    }
    ```

---

## 🔌 MODULE 3: EXACT API CONTRACT (FRONTEND FACING)
Patuhi struktur *routing* dan balasan *Cursor-based Pagination* ini 100%.

### 1. GET /api/v1/discovery/filter-options
*   **Query Params:** `mode` (contoh: building_team).
*   **Tugas:** Kembalikan Master Data katalog filter secara dinamis berdasarkan *mode*.
*   **Expected Response:** JSON berisi array `industries`, `skills`, `roles`, dan `languages`. Katalog yang tidak dipakai kembalikan array kosong `[]`.

### 2. POST /api/v1/discovery/cards
*   **Tugas:** Mengembalikan tumpukan kartu berdasarkan filter. Wajib menggunakan struktur Polymorphic: `entityType: "profile"` (dari tabel users) atau `entityType: "startup"` (dari tabel startups).
*   **Request Body Example:**
    ```json
    {
      "context": { "mode": "finding_cofounder" },
      "filters": { "industryIds": ["ind_ai"], "locationAvailability": { "latitude": -6.2, "longitude": 106.8, "distanceKm": 50 } },
      "pagination": { "limit": 10, "cursor": null }
    }
    ```
*   **Response 200 OK Example (Profile Variant):**
    ```json
    {
      "success": true,
      "data": {
        "items": [
          {
            "entityType": "profile",
            "id": "card_001",
            "profileId": "usr_ardi_001",
            "name": "Ardi Wijaya",
            "location": { "distanceKm": 3 },
            "match": { "score": 99, "label": "Top Match" }
          }
        ],
        "nextCursor": "card_002",
        "hasMore": true
      }
    }
    ```
    *(Catatan: Untuk `match.score`, sementara berikan nilai statis atau dummy (misal 90) sampai algoritma matematika final disetujui)*.

### 3. POST /api/v1/discovery/cards/:targetId/action
*   **Tugas:** Mencatat aksi swipe (`like`, `pass`, `super_like`).
*   **Request Body:** `{ "action": "like" }`
*   **Response 200 OK (Jika Mutual Match):**
    ```json
    {
      "success": true,
      "message": "Swipe action recorded successfully",
      "data": {
        "id": "card_001",
        "targetId": "usr_ardi_001",
        "profileId": "usr_ardi_001",
        "startupId": null,
        "action": "like",
        "isMatch": true,
        "matchId": "match_123"
      }
    }
    ```

---

## 🚀 YOUR EXECUTION TASK
1. Buat Controller khusus `DiscoveryController` untuk memproses 3 endpoint di atas.
2. Buat `FilterBuilderService` yang bertugas menerjemahkan *Nested JSON payload* dari parameter API Contract menjadi klausa `WHERE/WHERE IN` pada Eloquent Laravel.
3. Terapkan validasi `PREMIUM_REQUIRED` untuk filter berbayar.
4. Implementasikan metode *Cursor Pagination* (`nextCursor`) pada respon balasan *cards*, jangan gunakan `page/offset`.
