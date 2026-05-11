# API Hit Testing: Matchmaking, Discovery & Profile (Update)

Dokumen ini berisi panduan *testing* untuk API yang baru saja diimplementasikan pada sprint terkait **CON-50, CON-51, CON-59, CON-60, dan CON-69**.

Gunakan token *Bearer* dari user yang sudah login dan *onboarded* (`registration_step: 5`).

---

## 1. Feed & Discovery (Pro Boost) - `CON-50`

Mengambil data *feed* dengan algoritma yang sudah dioptimasi. User dengan `is_pro = true` akan mendapatkan *boost score* +25 sehingga muncul lebih atas.

**Endpoint:**
`GET {{base_url}}/api/v1/feed?limit=10&page=1`

**Headers:**
```json
{
  "Authorization": "Bearer {{auth_token}}",
  "Accept": "application/json"
}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Feed fetched successfully",
  "data": {
    "items": [
      {
        "id": "...",
        "profileId": "...",
        "name": "...",
        "compatibilityScore": 85.5 // (Akan lebih tinggi jika target user adalah Pro)
        // ...
      }
    ],
    "total": 100,
    "page": 1,
    "limit": 10,
    "hasMore": true
  }
}
```

---

## 2. Profile Response (Badges & Missing Fields) - `CON-59`

Mengambil profil *user*. Pastikan *badges* (seperti Premium atau Startup Founder) dan bahasa yang digunakan (*languages*) muncul di *highlights*.

**Endpoint:**
`GET {{base_url}}/api/v1/me/profile`

**Headers:**
```json
{
  "Authorization": "Bearer {{auth_token}}",
  "Accept": "application/json"
}
```

**Expected Response (200 OK):**
```json
{
    "id": "...",
    "profileType": "builder",
    "name": "...",
    "badges": [
        { "id": "premium", "label": "Premium" },
        { "id": "startup-founder", "label": "Startup Founder" }
    ],
    "sections": {
        "highlights": {
            "items": [
                "B.Sc Computer Science, Universitas Indonesia",
                "Speaks English, Indonesian"
            ]
        }
    }
}
```

---

## 3. "See Who Likes You" API - `CON-51`

Mengambil daftar *user* yang telah melakukan *Swipe Right* (Like) kepada Anda, tetapi Anda belum melakukan hal yang sama (belum *Match*). Fitur ini *gated* untuk user Premium.

**Endpoint:**
`GET {{base_url}}/api/v1/likes-you?limit=10&page=1`

**Headers:**
```json
{
  "Authorization": "Bearer {{auth_token}}",
  "Accept": "application/json"
}
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Likes fetched successfully",
  "data": {
    "locked": false, // true jika pemanggil bukan is_pro
    "items": [
      {
        "likeId": "...",
        "likedAt": "2026-05-12T10:00:00Z",
        "user": {
          "userId": "...",
          "name": "Budi",
          "photoUrl": "...",
          "headline": "Software Engineer",
          "location": "Jakarta, Indonesia"
        }
      }
    ],
    "total": 5,
    "page": 1,
    "limit": 10,
    "hasMore": false
  }
}
```

---

## 4. City Filter in Discovery Cards - `CON-60`

Meminta data kandidat dengan filter **City**.

**Endpoint:**
`POST {{base_url}}/api/v1/discovery/cards`

**Headers:**
```json
{
  "Authorization": "Bearer {{auth_token}}",
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

**Body (JSON):**
```json
{
  "context": {
    "mode": "finding_cofounder"
  },
  "filters": {
    "locationAvailability": {
      "city": "jakarta"
    }
  },
  "pagination": {
    "limit": 10,
    "cursor": null
  }
}
```

**Expected Response (200 OK):**
Akan me-*return* daftar *cards* dengan user yang memiliki lokasi `jakarta`.

---

## 5. Session Initialization - `CON-69`

Mengambil status sesi saat ini, *preference* awal untuk *discovery*, dan status *premium*.

**Endpoint:**
`GET {{base_url}}/api/v1/auth/session`

**Headers:**
```json
{
  "Authorization": "Bearer {{auth_token}}",
  "Accept": "application/json"
}
```

**Expected Response (200 OK):**
```json
{
  "status": "success",
  "message": "Session loaded.",
  "data": {
    "user": {
      "id": "...",
      "email": "user@example.com",
      "registration_step": 5,
      "is_active": true,
      "is_onboarded": true
    },
    "discovery_preferences": {
      "default_discovery_mode": "finding_cofounder" 
    },
    "premium": {
      "boost": 3,
      "spotlight": 1,
      "isPremium": true
    }
  }
}
```
*(Catatan: `default_discovery_mode` akan bernilai `null` jika `is_onboarded` bernilai `false`)*
