# 🤖 CLAUDE INSTRUCTION: ConnectX Matchmaking Engine (V1)
**Target:** Implementasi Matchmaking Backend & Filter (Laravel 11, PostgreSQL, Redis)
**Context:** Anda adalah Senior Backend Developer. Tugas Anda adalah membangun sistem *matchmaking* ConnectX. Sistem ini menggunakan arsitektur *3-Layer Hard Filter & Weighted Scoring* yang dijalankan secara *asynchronous* menggunakan Redis Queue.

## ⚠️ STRICT RULES (DO NOT IGNORE)
1. **LOCATION CALCULATION:** Gunakan perhitungan **Latitude dan Longitude** untuk mencari radius jarak (Haversine atau kalkulasi koordinat SQL). Kembalikan hasilnya ke dalam field `distance_km` pada JSON API [1].
2. **ASYNC SCORING:** Kalkulasi skor *Match* (Algoritma 4 Variabel untuk Free & 10 Variabel untuk Pro) wajib menggunakan *Laravel Jobs/Queue* (`GenerateMatchAnalysisJob`) yang di-*dispatch* ke Redis Worker.
3. **API CONTRACT COMPLIANCE:** Anda WAJIB mengikuti struktur *request* dan *response* JSON di bawah ini 100% tanpa mengubah nama field, karena ini adalah kontrak baku dengan tim Frontend [1, 3, 4].

---

## 🏗️ ARCHITECTURE & ALGORITHM SPECIFICATIONS

### Layer 1: Geo-Spatial Filter (Latitude & Longitude)
Gunakan parameter `distance_km` dan `location[]` dari Frontend [1]. Lakukan filter radius di Query Builder/Raw SQL menggunakan perhitungan Latitude dan Longitude. Hitung juga `LocationScore = (L x 5) + (R x 3) + (Rel x 2)` untuk prioritas.

### Layer 2: Requirement Hard Filter (Dynamic Querying)
Frontend akan mengirim parameter: `type` (person/startup), `tab` (cofounder/team), `industry[]`, `role[]`, `availability[]`, `stage[]` [1].
*   Jika `type = person`, *query* wajib menembak tabel `users` (P2P Mode).
*   Jika `type = startup`, *query* wajib menembak tabel `startups` (P2B Mode).
*   Terapkan parameter array (seperti `industry[]` dan `skills[]`) menggunakan klausa `WHERE IN` pada query database Anda.

### Layer 3: Weighted Scoring Engine (Asynchronous Job)
Normalisasi semua nilai mentah (0.0 - 1.0) sebelum dikali bobot persentase.
**A. Free Users Algorithm (4 Variables):**
`FreeScore = (Mode * 35%) + (Skill_Complementarity * 30%) + (Industry_Fit * 20%) + (Commitment_Fit * 15%)`
*   *Catatan:* Gunakan *Jaccard Similarity* untuk mencocokkan `industry[]`. Simpan hasil skor di Redis.

**B. Premium/Pro Users Algorithm (Decathlon - 10 Variables):**
Aktifkan jika `is_pro = true`.
`PremiumScore = (FreeScore Variables) + Stage (10%) + Location_Fit (8%) + Experience (5%) + Leadership (5%) + Language (5%) + Education (5%)`
*   *Golden Threshold:* Buang kandidat Pro yang skornya di bawah 70%.

---

## 🔌 EXACT API CONTRACT (FRONTEND FACING)

### 1. GET /api/v1/feed
*   **Query Params:** `type`, `tab`, `industry[]`, `role[]`, `availability[]`, `location[]`, `stage[]`, `distance_km`, `page`, `limit` [1].
*   **Expected Response (200 OK):**
```json
{
  "cards": [{
    "id": "card_usr123",
    "type": "person",
    "user_id": "usr_123",
    "name": "Ardi Wijaya",
    "age": 28,
    "avatar_url": "https://...",
    "location": "Jakarta, Indonesia",
    "distance_km": 3,
    "match_percentage": 98,
    "match_label": "Perfect Match",
    "headline": "Full-Stack Engineer",
    "looking_for": "co-founder",
    "stage": "mvp",
    "bio": "Building the future of...",
    "startup_idea": { "title": "FinPay", "description": "..." },
    "industries": ["fintech", "saas"],
    "skills": ["React Native", "Node.js"],
    "availability": "full_time",
    "languages": ["English", "Bahasa Indonesia"],
    "cofounder_type": "technical"
  }],
  "pagination": { "page": 1, "limit": 10, "total": 45, "has_next": true },
  "filters_applied": { "industry": ["fintech"], "tab": "cofounder" }
}
(Catatan: Jika kosong kembalikan JSON empty_state sesuai kontrak baku)
2. POST /api/v1/swipe/connect
Request Body: { "card_id": "card_usr123", "target_user_id": "usr_123" }
Expected Response (Jika Mutual Match - 200 OK):
{
  "swiped": true,
  "is_match": true,
  "match": {
    "match_id": "mtc_789",
    "matched_with": { "user_id": "usr_123", "name": "Ardi Wijaya", "avatar_url": "..." },
    "match_type": "user_to_user",
    "match_message": "You're connected",
    "expires_at": "2026-04-18T10:00:00Z",
    "conversation_id": "conv_abc"
  }
}
(Catatan: Jika tidak match, kembalikan: { "swiped": true, "is_match": false })
3. POST /api/v1/swipe/skip
Request Body: { "card_id": "card_usr123", "target_user_id": "usr_123" }
Expected Response (200 OK): { "skipped": true }
4. GET /api/v1/feed/filters
Expected Response (200 OK): { "industries": [{ "value": "saas", "label": "SaaS" }], "roles": [...], "availability": [...], "locations": [...], "stages": [...] }
5. POST /api/v1/swipe/rewind (PRO ONLY)
Expected Response (200 OK): { "rewound": true, "card": { ... } }
Expected Errors: 403 pro_required atau 400 no_swipe_to_rewind
.

--------------------------------------------------------------------------------
🚀 YOUR EXECUTION TASK
Berdasarkan instruksi di atas, buatkan saya:
Controller API untuk kelima endpoint di atas dengan validasi request parameter.
Service Class yang mengeksekusi Layer 1 (Kalkulasi Lat/Long) dan Layer 2 (Hard Filter Array).
Background Job GenerateMatchAnalysisJob.php untuk menjalankan matematika skor algoritma Free & Pro.
Integrasikan WebSocket trigger match.created saat is_match: true
.

***

