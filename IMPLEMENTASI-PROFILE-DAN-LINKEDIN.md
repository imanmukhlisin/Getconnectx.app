# Implementasi Profile & LinkedIn Sync (Asynchronous)

Dokumen ini merangkum arsitektur, alur sistem, dan pembaruan _database_ untuk fitur **LinkedIn Profile Synchronization** serta pengelolaan profil pengguna yang sesuai dengan kontrak `API-PROFILE-LINKEDIN.md`.

---

## 1. Arsitektur Asinkron (Apify Webhook)

Pengambilan data profil LinkedIn menggunakan **Apify Scraper** memerlukan waktu 2-5 menit. Arsitektur menggunakan **Webhook-Driven Workflow** agar tidak memblokir performa aplikasi.

### Komponen Utama
1. **Trigger (FE)**: `POST /api/v1/auth/linkedin-sync` (Memicu scraping & simpan device info).
2. **Scraper (Apify Cloud)**: Melakukan crawling data LinkedIn secara eksternal.
3. **Webhook Receiver**: `POST /api/v1/webhooks/apify/linkedin` (Menangkap sinyal sukses dari Apify).
4. **Job Processor**: `ProcessLinkedInProfileJob` (Mengolah dataset, memanggil AI Gemini, dan update DB).

---

## 2. API Reference & Contract Compliance

Seluruh endpoint profil telah di-_refactor_ menggunakan `ProfileResource` guna mendukung struktur JSON yang dibutuhkan Frontend (terutama bagian `sections` dan `location`).

### A. Endpoint List
| Method | URI | Deskripsi | Auth |
| :--- | :--- | :--- | :--- |
| **GET** | `/api/v1/me/profile` | Ambil profil user yang sedang login | Sanctum |
| **PATCH** | `/api/v1/me/profile` | Update profil (partial update) | Sanctum |
| **GET** | `/api/v1/profiles/{id}` | Ambil profil publik pengguna lain | Sanctum |
| **GET** | `/api/v1/profile-options` | Ambil daftar opsi Master Data (Tags/Hobbies) | Sanctum |
| **POST** | `/api/v1/auth/linkedin-sync` | Inisiasi sinkronisasi LinkedIn via Apify | Sanctum |

### B. Konsep Baru: `ProfileResource` Data Mapping
Backend mengimplementasikan logika dinamis pada `App\Http\Resources\ProfileResource` untuk memenuhi kebutuhan UI:

1. **Sections: About**
   - Jika user memiliki relasi `startup`, maka `kind` menjadi `startupIdea` dan `value` diambil dari kolom `startup_idea`.
   - Jika user tidak memiliki startup, maka `kind` menjadi `personalDescription` dan `value` diambil dari kolom `bio`.
2. **Location Objects**
   - Request PATCH mengirim string tunggal: `"Jakarta, Indonesia"`.
   - Backend melakukan `explode` untuk menyimpan ke kolom `city` dan `country`.
   - Response mengembalikan object: `{"city": "Jakarta", "country": "Indonesia", "display": "Jakarta, Indonesia"}`.
3. **Personality & Hobbies**
   - Di-mapping otomatis dari relasi `tags` yang memiliki tipe `personality`, `hobby`, atau `personality_hobbies`.

---

## 3. Contoh Request & Response

### GET `/api/v1/me/profile`
**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "name": "Alex Doe",
    "headline": "Product Manager",
    "location": {
      "city": "Jakarta",
      "country": "Indonesia",
      "display": "Jakarta, Indonesia"
    },
    "sections": {
      "about": {
        "kind": "personalDescription",
        "title": "Description",
        "value": "Experienced PM with focus on AI products..."
      },
      "personalityAndHobbies": {
        "title": "Personality & Hobbies",
        "items": [
          { "id": 1, "name": "Adventurous" },
          { "id": 5, "name": "Gaming" }
        ]
      }
    }
  }
}
```

### PATCH `/api/v1/me/profile`
**Request Payload:**
```json
{
  "name": "Alex Update",
  "headline": "Senior Product Manager",
  "location": "Singapore, Singapore",
  "about": "New bio description...",
  "personalityAndHobbyIds": [1, 5, 10]
}
```

---

## 4. Cara Menjalankan Sinkronisasi LinkedIn (Testing)

### Manual Trigger (Frontend Simulator)
Hit endpoint berikut menggunakan Bearer Token user:
**POST** `{{base_url}}/api/v1/auth/linkedin-sync`
```json
{
  "linkedin_url": "https://www.linkedin.com/in/username",
  "fcm_token": "fcm_token_device",
  "device_id": "iphone_15_pro"
}
```

### Simulasi Webhook (Manual Testing tanpa Apify)
Jika ingin melewati proses scraping dan langsung menjalankan Job, tembak endpoint Webhook dengan token rahasia:
**POST** `/api/v1/webhooks/apify/linkedin?user_id=[USER_UUID]&token=[WEBHOOK_TOKEN_FROM_ENV]`
**Payload:**
```json
{
  "resource": {
    "defaultDatasetId": "dataset-id-yang-sudah-ada-di-apify"
  }
}
```

---

## 5. Database Rule (Critical)

> [!IMPORTANT]
> **Experience & Education Data**:
> Kolom `experience` dan `education` pada tabel `user_credentials` disimpan dalam format JSONB. Job `ProcessLinkedInProfileJob` menjamin data selalu berupa **Array Kosong `[]`** jika data tidak ditemukan.
> **DILARANG KERAS** mengubah data menjadi `NULL` karena akan merusak logika *Matchmaking Scoring Engine* (Constraint Variabel G & J).

---
`--- Dokumentasi Terakhir Diperbarui: 2026-04-28 ---`