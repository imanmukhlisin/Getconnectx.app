# 🚀 ConnectX Matchmaking & Discovery API

Dokumen ini adalah gabungan panduan *endpoint* untuk sistem Matchmaking V1 (Connect System) dan sistem Filter Discovery V2 terbaru. Gunakan struktur JSON di bawah ini untuk dites via Postman atau diintegrasikan ke Frontend asumsikan selalu membawa `Authorization: Bearer <TOKEN>`.

---

## 🌟 BAGIAN I: DISCOVERY FILTER V2 (Pencarian Spesifik)

### 1. Get Filter Options (Catalog Master Data)
Digunakan untuk mengambil data dropdown filter (Industries, Skills, Roles, Languages) sesuai dengan mode (P2P / P2B).
*   **Endpoint**: `GET {{base_url}}/api/v1/discovery/filter-options?mode=finding_cofounder`
*   **Query**: `mode` (finding_cofounder, building_team, explore_startups, joining_startups)
*   **Expected Response (200 OK)**:
```json
{
  "success": true,
  "message": "Discovery filter options fetched successfully",
  "data": {
    "mode": "finding_cofounder",
    "industries": [ { "id": "grp_industry_core_technology", "label": "Core Technology", "options": [ { "id": "ind_ai", "label": "AI" } ] } ],
    "skills": [],
    "roles": [],
    "languages": []
  }
}
```

### 2. Fetch Cards (Profile Mode / P2P)
Mencari Cofounder atau tim (mereturn User Profile Cards).
*   **Endpoint**: `POST {{base_url}}/api/v1/discovery/cards`
*   **Body JSON**:
```json
{
  "context": { "mode": "finding_cofounder" },
  "filters": {
    "industryIds": ["ind_ai"],
    "locationAvailability": {
      "distanceKm": 50,
      "latitude": -6.200000,
      "longitude": 106.816666
    }
  },
  "pagination": { "limit": 10, "cursor": null }
}
```
*   **Expected Response (200 OK)**:
```json
{
  "success": true,
  "message": "Discovery cards fetched successfully",
  "data": {
    "aiInsight": "As a Backend Developer in Tech searching for a Co-Founder, ConnectX's precise filtering uniquely connects you with business-minded leaders in AI.",
    "items": [
      {
        "entityType": "profile",
        "id": "card_a1b2c3_0",
        "profileId": "9b...-uuid-here",
        "photoUrl": "https://.../avatar.jpg",
        "name": "Budi Santoso",
        "age": 28,
        "headline": "Business Dev at Tech",
        "location": {
          "city": "Jakarta",
          "country": "Indonesia",
          "display": "Jakarta, Indonesia",
          "distanceKm": 12.5
        },
        "match": { "score": 92, "label": "Perfect Match" },
        "badges": [],
        "bio": "Experienced bizdev."
      }
    ],
    "nextCursor": "9b...-uuid-here",
    "hasMore": true
  }
}
```

### 3. Fetch Cards (Startup Mode / P2B)
Mencari Startup yang mau di-join.
*   **Endpoint**: `POST {{base_url}}/api/v1/discovery/cards`
*   **Body JSON**:
```json
{
  "context": { "mode": "explore_startups" },
  "filters": { "startupStageIds": ["stage_seed"] },
  "pagination": { "limit": 10, "cursor": null }
}
```
*   **Expected Response (200 OK)**:
```json
{
  "success": true,
  "message": "Discovery cards fetched successfully",
  "data": {
    "aiInsight": "ConnectX analyzes your background to find ideal Seed-stage startups.",
    "items": [
      {
        "entityType": "startup",
        "id": "startup_card_d4e5f6",
        "startupId": "8a...-uuid-here",
        "name": "MediCure AI",
        "logoUrl": "https://.../logo.png",
        "badge": { "label": "SEED" },
        "founder": { "name": "Siti Aminah", "title": "Founder" },
        "match": { "score": 88, "label": "Strong Match" },
        "industry": { "primary": "Healthtech", "display": "Healthtech" },
        "team": { "memberCount": 5, "display": "5 members" },
        "summary": "AI-driven health diagnosis",
        "openRoles": [],
        "lookingFor": [],
        "teamStage": {
          "teamSize": 5,
          "stage": "SEED",
          "industry": "Healthtech",
          "hiringCount": 0
        },
        "journey": {
          "currentStage": "seed",
          "stages": [
            { "id": "idea", "label": "Idea", "state": "completed" },
            { "id": "mvp", "label": "MVP", "state": "completed" },
            { "id": "pre_seed", "label": "Pre-Seed", "state": "completed" },
            { "id": "seed", "label": "Seed", "state": "current" }
          ]
        }
      }
    ],
    "nextCursor": null,
    "hasMore": false
  }
}
```

### 4. Rewind Action (Premium Only)
Mengembalikan urung Swipe terakhir (dengan asumsi belum match).
*   **Endpoint**: `POST {{base_url}}/api/v1/discovery/swipes/rewind?mode=explore_startups`
*   **Query**: `mode` (optional: finding_cofounder, building_team, explore_startups, joining_startups)
*   **Body JSON**: `{}`
*   **Expected Response (200 OK)**:
```json
{
  "success": true,
  "message": "Last swipe rewound.",
  "data": {
    "profileId": "9b...-uuid-here",
    "rewoundAction": "pass",
    "card": {
      "entityType": "profile",
      "id": "card_a1b2c3_0",
      "profileId": "9b...-uuid-here",
      "name": "Ardi Wijaya"
    }
  }
}
```
*   *(Return `403` jika non-premium -> `code: DISCOVERY_REWIND_PREMIUM_REQUIRED`)*
*   *(Return `409` jika history kosong -> `code: DISCOVERY_REWIND_NOT_AVAILABLE`)*

---

## 💥 BAGIAN II: MATCHMAKING SWIPING (Aksi Kartu V1 & V2)

### 5. Swipe Action (V2 Discovery Cards Target)
Melakukan aksi swipe dari data Card yang di-fetch via Discovery V2.
*   **Endpoint**: `POST {{base_url}}/api/v1/discovery/cards/:targetId/action`
*   **Body JSON**:
```json
{
  "action": "like" 
}
```
*   **Expected Response (200 OK)**:
```json
{
  "success": true,
  "message": "Swipe action recorded successfully",
  "data": {
    "id": "card_xyz123",
    "targetId": "usr_xxxxxx-yyyy",
    "profileId": "usr_xxxxxx-yyyy",
    "startupId": null,
    "action": "like",
    "isMatch": true,
    "matchId": "uuid-match-xxxx"
  }
}
```

### 6. Legacy Swipe (V1)
Meskipun V2 di atas lebih disarankan, endpoint swipe V1 tetap tersedia.
*   **Connect (Swipe Right)**: `POST {{base_url}}/api/v1/swipe/connect`
     *Body:* `{"to_user_id": "<UUID>"}`
*   **Skip (Swipe Left)**: `POST {{base_url}}/api/v1/swipe/skip`
     *Body:* `{"to_user_id": "<UUID>"}`

---

## 🤝 BAGIAN III: MATCH LIST & ANALYSIS

### 7. Get All Matches (List Teman Chat)
Menampilkan siapa saja yang sudah *mutual match* dengan user berserta indikator chat ID-nya.
*   **Endpoint**: `GET {{base_url}}/api/v1/matches`

### 8. View Match Analysis (AI / Compatibility)
Mengambil summary komputasi (Layer 3 Score & Compatibility JSON) yang dihiatus / diproses secara *background* queue di database.
*   **Endpoint**: `GET {{base_url}}/api/v1/matches/{matchId}/analysis` 
    *(Contoh: `mtc_83b320d0f41...`)*
