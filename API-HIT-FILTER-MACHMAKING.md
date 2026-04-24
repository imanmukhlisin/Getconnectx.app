# ConnectX Discovery Filter API V2 — Postman Hit Guide

Dokumen ini berisi panduan dan contoh payload untuk melakukan testing endpoint Discovery Filter (V2) via Postman. Frontend bisa menyalin format JSON API request di bawah ini.

> [!IMPORTANT]
> **Prerequisites:**
> Semua endpoint ini membutuhkan Bearer Token Auth. Pastikan Anda punya token dari login user yang sudah melewati onboarding minimal step 5.
> `Header: Authorization: Bearer <your_token>`
> `Header: Accept: application/json`

---

## 1. Get Filter Options (Catalog)

Endpoint ini digunakan untuk mengambil master data (opsi) form filter berdasarkan mode yang sedang aktif.

**Endpoint:**
`GET {{base_url}}/api/v1/discovery/filter-options?mode=building_team`

**Query Parameters:**
- `mode` (required): `finding_cofounder`, `building_team`, `explore_startups`, atau `joining_startups`.

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Discovery filter options fetched successfully",
  "data": {
    "mode": "building_team",
    "industries": [
      {
        "id": "grp_industry_core_technology",
        "label": "Core Technology",
        "options": [
          { "id": "ind_ai", "label": "AI" },
          { "id": "ind_fintech", "label": "Fintech" },
          { "id": "ind_web3", "label": "Web3" }
        ]
      }
    ],
    "skills": [
      // ... skill groups ...
    ],
    "roles": [
      // ... role groups ...
    ],
    "languages": [
      // ... language groups ...
    ]
  }
}
```

---

## 2. Fetch Cards — Profile Mode (P2P)

Digunakan untuk mode `finding_cofounder` dan `building_team`. Mengembalikan data **User (Profile) Cards**.

**Endpoint:**
`POST {{base_url}}/api/v1/discovery/cards`

**Request Body (JSON):**
Contoh user mencari co-founder dengan skill Business / Finance di industri AI & Fintech wilayah Hybrid/Remote.
```json
{
  "context": {
    "mode": "finding_cofounder"
  },
  "filters": {
    "goalId": "goal_finding_cofounder",
    "skillStrengthIds": ["ss_business", "ss_finance"],
    "industryIds": ["ind_ai", "ind_fintech"],
    "locationAvailability": {
      "workArrangementIds": ["wa_remote", "wa_hybrid"],
      "remoteReady": true,
      "distanceKm": 50,
      "latitude": -6.200000,
      "longitude": 106.816666
    }
  },
  "pagination": {
    "limit": 10,
    "cursor": null
  }
}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Discovery cards fetched successfully",
  "data": {
    "aiInsight": "As a Backend Developer in Tech searching for a Co-Founder, ConnectX's precise filtering uniquely connects you with business-minded leaders in AI and Fintech ready for hybrid building.",
    "items": [
      {
        "entityType": "profile",
        "id": "card_a1b2c3_0",
        "profileId": "9b...-uuid-here",
        "photoUrl": "https://.../avatar.jpg",
        "name": "Budi Santoso",
        "age": 28,
        "headline": "Business Dev at TechCorp",
        "location": {
          "city": "Jakarta",
          "country": "Indonesia",
          "display": "Jakarta, Indonesia",
          "distanceKm": 12.5
        },
        "match": {
          "score": 92,
          "label": "Perfect Match"
        },
        "badges": [
          { "id": "badge_pro", "label": "Pro", "icon": "sparkles" }
        ],
        "bio": "Experienced bizdev looking to build the next big thing in Fintech.",
        "startupIdea": null,
        "interests": [],
        "skills": [],
        "experience": [],
        "education": [],
        "languages": []
      }
    ],
    "nextCursor": "9b...-uuid-here",
    "hasMore": true
  }
}
```

---

## 3. Fetch Cards — Startup Mode (P2B)

Digunakan untuk mode `explore_startups` dan `joining_startups`. Mengembalikan data **Startup Cards**.

**Endpoint:**
`POST {{base_url}}/api/v1/discovery/cards`

**Request Body (JSON):**
Contoh user mencari startup di industri Healthtech tahap Seed.
```json
{
  "context": {
    "mode": "explore_startups"
  },
  "filters": {
    "goalId": "goal_explore_startups",
    "startupStageIds": ["stage_seed"],
    "industryIds": ["ind_healthtech"],
    "locationAvailability": {
      "distanceKm": 10,
      "latitude": -6.200000,
      "longitude": 106.816666
    }
  },
  "pagination": {
    "limit": 10,
    "cursor": null
  }
}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Discovery cards fetched successfully",
  "data": {
    "aiInsight": "ConnectX analyzes your Backend background to find ideal Seed-stage Healthtech startups actively hiring in your area.",
    "items": [
      {
        "entityType": "startup",
        "id": "startup_card_d4e5f6",
        "startupId": "8a...-uuid-here",
        "name": "MediCure AI",
        "logoUrl": "https://.../logo.png",
        "badge": {
          "label": "SEED"
        },
        "founder": {
          "name": "Siti Aminah",
          "title": "Founder"
        },
        "match": {
          "score": 88,
          "label": "Strong Match"
        },
        "industry": {
          "primary": "Healthtech",
          "secondary": "AI",
          "display": "Healthtech / AI"
        },
        "team": {
          "memberCount": 5,
          "display": "5 members"
        },
        "summary": "Building AI diagnostics for rural clinics.",
        "openRoles": [
          { "id": "role_1", "title": "Senior Backend" }
        ],
        "lookingFor": ["Team members"],
        "teamStage": {
          "teamSize": 5,
          "stage": "SEED",
          "industry": "Healthtech / AI",
          "hiringCount": 1
        },
        "journey": {
           "currentStage": "seed",
           "stages": []
        }
      }
    ],
    "nextCursor": null,
    "hasMore": false
  }
}
```

---

## 4. Fetch Cards (Error Premium / Hard-Block)

Kalau User Biasa (`is_pro: false`) mencoba filter atribut premium, API akan me-return 403.

**Endpoint:**
`POST {{base_url}}/api/v1/discovery/cards`

**Request Body (JSON) (User Free mencoba submit fitur AI Precision):**
```json
{
  "context": {
    "mode": "finding_cofounder"
  },
  "filters": {
    "aiMatchPrecision": {
      "minimumMatchScore": 90
    }
  }
}
```

**Expected Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "Premium subscription required to use advanced discovery filters",
  "error": {
    "code": "PREMIUM_REQUIRED"
  }
}
```

---

## 5. Swipe Action

Digunakan untuk trigger Connect (Like) / Skip (Pass). Ini akan terintegrasi langsung dengan mekanisme Match lama lo.

**Endpoint:**
`POST {{base_url}}/api/v1/discovery/cards/:targetId/action`
> *Value `:targetId` diambil dari `profileId` atau `startupId` pada list card.*

**Request Body (JSON):**
```json
{
  "action": "like"
}
```
*(Action valid: `like`, `pass`, `super_like`)*

**Expected Response (200 OK):**
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

> [!TIP]
> Jika `isMatch: true`, sebuah Row baru terbuat di table `UserMatch` dan percakapan otomatis dibikinkan di table `Conversations`.
