# BACKLOG CON-73: Profile API, Smart Slug Formatter & Master Data Catalogs

Dokumen ini merupakan spesifikasi lengkap mengenai perombakan arsitektur *Profile Data Exposure* dan mekanisme pembaruan profil yang telah diselaraskan dengan aturan Onboarding Seeder.

## 1. Konteks Masalah
Sebelumnya, Frontend mengalami beberapa kendala dalam membangun UI Edit Profile:
1. Payload yang dikembalikan `GET /api/v1/me/profile` terlalu kaku dan terkurasi.
2. Form onboarding sering *redundant* saat *Switch Mode* (mengulang dari pengisian Nama & TTL).
3. Nilai yang dikembalikan sering berupa *slug* database (`software_engineer`), yang memaksa FE melakukan pemetaan manual ke string yang *human-readable*.
4. Kebingungan terkait ketersediaan endpoint untuk mengambil data opsi *dropdown* (Kota, Industri, Roles, dll).
5. FE butuh kapabilitas untuk mengubah/meng-update data mentah tersebut dengan validasi yang akurat.

---

## 2. Fitur 1: Re-Onboarding Fast-Forward (Switch Mode)
Saat ini, jika User **sudah menyelesaikan onboarding** (flag `is_onboarded = true`), lalu ia membuat sesi onboarding baru (karena ingin *switch mode* dari Founder ke Builder atau sebaliknya), Backend **tidak akan lagi** melemparkannya ke halaman pengisian biodata (Nama, TTL, dll).

Sesi akan langsung **di-fast-forward** menuju Step ke-5 (`step_role_selection` / *"Kamu mau pakai ConnectX buat apa?"*). 
Hal ini membuat *UX Switch Mode* sangat mulus dan bebas redundansi.

---

## 3. Fitur 2: API "Sapu Jagat" (`userRaw` & `startupRaw`)
Endpoint `GET /api/v1/me/profile` kini mengembalikan **seluruh kolom database** tanpa terkecuali, diformat khusus untuk kenyamanan Frontend.

### Struktur Kembalian
```json
{
  "success": true,
  "data": {
    "id": "usr_...",
    // ... [Field terformat UI-ready bawaan lama tetap ada dan aman] ...

    "userRaw": {
        // Berisi SEMUA kolom dari tabel users
        "name": "Budi Santoso",
        "city": "Jakarta",
        "roleCategory": "Startup",
        "isOnboarded": true,
        "languages": ["Indonesian", "English"],

        // Injeksi Data LinkedIn Penuh
        "linkedinData": {
            "urn": "urn:li:member:123456789",
            "headline": "CEO at ConnectX",
            "experience": [ ... ],
            "education": [ ... ],
            "certifications": [ ... ]
        }
    },

    "startupRaw": {
        // Berisi SEMUA kolom dari tabel startups
        "name": "ConnectX",
        "stage": "Pre Seed",
        "industry": "Financial Technology",
        "openRoles": ["Backend Developer", "Chief Technology Officer"],
        
        // Data dari onboarding (Traction & Offering)
        "lookingFor": {
            "revenue": "$10k MRR",
            "equity": "10%",
            ...
        }
    }
  }
}
```

### Konversi CamelCase
Seluruh nama *key* dari tabel (seperti `is_onboarded` atau `role_category`) otomatis di-transformasi menjadi **camelCase** (`isOnboarded`, `roleCategory`) agar FE bisa langsung *destructuring* variabel di JavaScript tanpa masalah *linting*.

---

## 4. Fitur 3: Smart Slug Formatter (Anti-Underscore)
Di dalam `userRaw` dan `startupRaw`, ada mekanisme filter pintar yang otomatis mengubah nilai *slug* database menjadi *Human Readable String*.

**Field yang terpengaruh (dan otomatis dibersihkan):**
- `userRaw.roleCategory`
- `startupRaw.industry`
- `startupRaw.secondaryIndustry`
- `startupRaw.stage`
- `startupRaw.openRoles` (Setiap elemen di dalam array ini dibersihkan)

**Contoh:** `software_engineer` ➡️ `Software Engineer`.

**Proteksi:** Filter ini bekerja **eksklusif** hanya pada daftar field di atas. Field lain seperti email, Bio/Deskripsi, dan URL (`linkedinData`) dijamin 100% utuh dan tidak rusak oleh kapitalisasi.

---

## 5. Fitur 4: Pembaruan Profil & Reversal Slug (Sesuai Seeder)
Untuk menyeimbangkan fitur *Smart Slug Formatter* di atas, fitur UPDATE (`PATCH`) profil memiliki kapabilitas khusus.

Ketika Frontend merender Form Edit Profile menggunakan data `Software Engineer`, Frontend **bebas** mengirimkan kembali teks `"Software Engineer"` tersebut ke backend.
Backend (melalui `StartupProfileController` dan `ProfileController`) akan melakukan **Reversal Slug Format** (mengubah string tersebut kembali menjadi `software_engineer`) sebelum disimpan ke database.

Hal ini **menjamin data integrasi algoritma Discovery dan Matchmaking** tetap berjalan sempurna, karena nilai yang tersimpan di DB akan 100% *matching* dengan *slug value* yang dihasilkan oleh `OnboardingSeeder`.

**Endpoint Pembaruan:**
- `PATCH /api/v1/me/profile`: Update User Profile (bisa update `avatar_url`, `birthday`, `gender`, `languages`, `city`, dll).
- `PATCH /api/v1/me/startup`: Update Startup Profile (bisa update `logo_url`, `stage`, `industry`, `open_roles`, flat payload `looking_for`, dll).

---

## 6. Fitur 5: Master Data & Catalog Endpoints (Dropdown Opsi FE)
Agar Frontend bisa menyajikan *Dropdown Options* / *Select Input* yang akurat, Frontend **DIWAJIBKAN** untuk menggunakan endpoint master data berikut, dan DILARANG melakukan *hardcode* terhadap opsi industri/role.

Berikut adalah tiga pilar endpoint katalog untuk FE:

### A. Katalog Lokasi & Hobi User
`GET /api/v1/profile-options`
**Kegunaan:** Menarik daftar *Kota/Negara* dan daftar spesifik *Personality & Hobbies*.
```json
{
  "data": {
    "locations": [ { "id": "JKT", "label": "Jakarta", "group": "Indonesia" } ],
    "personalityAndHobbies": [ { "id": "ph_1", "name": "Berenang" } ]
  }
}
```

### B. Katalog Filter Discovery (Industri, Skill, Role)
`GET /api/v1/discovery/filter-options`
**Kegunaan:** Menarik semua entitas katalog profesional (Sangat krusial untuk form edit `industry`, `skills`, `open_roles`, `stage`, dll).
Ini mengembalikan *Value* (slug) dan *Label* (Human readable).

### C. Katalog Spesifik Pertanyaan Onboarding
`GET /api/v1/onboarding/options/search?question_id={id}`
**Kegunaan:** Jika FE butuh dropdown yang sangat-sangat *niche* sesuai pertanyaan onboarding (misalnya: Mata uang gaji untuk `q_su_salary_currency`), tembak endpoint ini dengan memasukkan `question_id`-nya.

---
**Status Dokumen:** 🟢 Finalized & Implemented.
**Disetujui Oleh:** Backend & Product.
