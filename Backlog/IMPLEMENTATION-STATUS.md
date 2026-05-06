# Backend Implementation Status
> Last updated: 2026-05-06  
> Updated by: Antigravity (AI Backend Assistant)  
> Branch: `develop`

---

## Legend
| Status | Arti |
|---|---|
| ✅ Done | Sudah diimplementasikan dan di-push ke `develop` |
| ⚠️ Partial | Sebagian diimplementasikan, ada catatan |
| ❌ Not Yet | Belum diimplementasikan |
| 🔒 Gated | Implemented tapi butuh kondisi premium |

---

## CON-59 — Profile Response
**Status: ✅ Done**

| Sub-feature | Status | Catatan |
|---|---|---|
| `GET /api/v1/me/profile` | ✅ Done | Bearer token auth, resolve user dari token |
| `PATCH /api/v1/me/profile` | ✅ Done | Update profile data |
| `GET /api/v1/profiles/{id}` | ✅ Done | Lihat profil user lain (public) |
| `GET /api/v1/profile-options` | ✅ Done | Daftar opsi untuk edit profile |
| `PUT /api/v1/profile/fcm-token` | ✅ Done | Register/update FCM token dari device |
| `startupIdea` vs `personalDescription` | ✅ Done | Backend menentukan variant berdasarkan onboarding state |
| LinkedIn scraping → profile sync | ✅ Done | Apify HarvestAPI scraper, webhook callback, semua field dimapping termasuk `avatar_url`, `position`, `location`, `experience`, `education` |

**Catatan penting untuk FE:**
- `profileId` di response adalah UUID user, bukan prefixed string
- Field `credential` di response memuat data LinkedIn (photo, headline, experience, education)

---

## CON-60 — Swipe Discovery Card Stack
**Status: ✅ Done**

| Sub-feature | Status | Catatan |
|---|---|---|
| `GET /api/v1/discovery/filter-options?mode=<mode>` | ✅ Done | Return `city`, `industries`, `skills`, `roles`, `availability`, `equity`, `languages` per mode |
| `POST /api/v1/discovery/cards` | ✅ Done | Paginated, cursor-based, mode-aware |
| `POST /api/v1/discovery/cards/:targetId/action` | ✅ Done | `like`, `pass`, `super_like` |
| City filter di `filter-options` | ✅ Done | 200+ kota Indonesia + Asia + global, grouped per region |
| City filter di `POST /cards` | ✅ Done | `filters.city` atau `filters.locationAvailability.city` |
| Exclude swiped users | ✅ Done | Exclude via `likes` + `user_matches` table |
| Exclude self dari feed | ✅ Done | |
| Premium filter validation | ✅ Done | Return `PREMIUM_REQUIRED` untuk non-pro users |
| Match score calculation | ✅ Done | Algoritma berbasis tag compatibility |
| `entityType: "profile"` | ✅ Done | Mode `finding_cofounder`, `building_team` |
| `entityType: "startup"` | ⚠️ Partial | Mode `explore_startups`, `joining_startups` — startup query ada, perlu validasi data startup di DB |
| `conversationId` di swipe response saat match | ✅ Done | Fix: sebelumnya tidak di-return ke FE |
| UUID validation pada `targetId` | ✅ Done | Guard: return 422 jika `targetId` bukan UUID (misal `card_xxxx`) |

**Penting untuk FE:**
- `:targetId` di URL harus pakai `profileId` (UUID) dari response cards, **bukan** field `id` (`card_xxxx`)
- Saat mutual match: response include `isMatch: true`, `matchId`, `conversationId` → FE langsung navigate ke chat room

---

## CON-60 Update — City List di Filter Options
**Status: ✅ Done**

City catalog sudah include 200+ kota di:
- Indonesia (lengkap semua ibu kota provinsi + kota besar)
- Asia Tenggara (SG, MY, TH, VN, PH, KH, MM, TL)
- Asia Selatan, Asia Timur, Timur Tengah
- Eropa Barat/Utara/Selatan/Timur
- Amerika Utara & Latin, Afrika, Oseania
- `remote` (opsi "Mana Saja")

---

## CON-64 — RevenueCat Integration (Premium)
**Status: ❌ Not Yet**

| Sub-feature | Status | Catatan |
|---|---|---|
| RevenueCat webhook integration | ❌ Not Yet | Belum ada endpoint webhook RevenueCat |
| `is_pro` flag update via RevenueCat | ❌ Not Yet | |
| `super_like` premium enforcement | ⚠️ Partial | Action `super_like` diterima di route, tapi belum ada premium gate khusus |
| Boost/spotlight credit system | ❌ Not Yet | Field ada di session contract tapi belum di-track di DB |

---

## CON-65 — Discovery Rewind (Premium Gated)
**Status: ⚠️ Partial**

| Sub-feature | Status | Catatan |
|---|---|---|
| `POST /api/v1/discovery/swipes/rewind` | ⚠️ Partial | Route ada, controller method ada (`rewind`), perlu verifikasi logic rewind & premium gate |
| Return restored card payload | ❌ Not Yet | Response rewind belum include full card data |
| `DISCOVERY_REWIND_PREMIUM_REQUIRED` (403) | ❌ Not Yet | Premium gate belum diimplementasi |
| `DISCOVERY_REWIND_NOT_AVAILABLE` (409) | ❌ Not Yet | Reason codes belum diimplementasi |

---

## CON-69 — Session Endpoint
**Status: ✅ Done**

| Sub-feature | Status | Catatan |
|---|---|---|
| `GET /api/v1/auth/session` | ✅ Done | Return user state, onboarding progress |
| `discovery_preferences.default_discovery_mode` | ✅ Done | Dari onboarding answer |
| `premium.isPremium` | ✅ Done | Dari field `is_pro` |
| `premium.boost` & `premium.spotlight` | ⚠️ Partial | Field ada tapi credit system belum diimplementasi (selalu return 0) |

---

## Fitur Tambahan (Di luar backlog CON-xx)

### LinkedIn Scraping Pipeline
**Status: ✅ Done**

| Sub-feature | Status |
|---|---|
| `POST /api/v1/auth/linkedin-sync` | ✅ Done |
| Apify HarvestAPI trigger | ✅ Done |
| Webhook callback `POST /api/v1/webhooks/apify/linkedin` | ✅ Done |
| Mapping: `avatar_url`, `position`, `location`, `experience`, `education` | ✅ Done |
| `companyLogo`, `schoolLogo` di experience/education | ✅ Done |

### Matchmaking & Chat
**Status: ✅ Done**

| Sub-feature | Status | Catatan |
|---|---|---|
| Mutual match detection | ✅ Done | Via `likes` table, check `reverseLike` |
| Auto-create conversation saat mutual match | ✅ Done | |
| `GenerateMatchAnalysisJob` | ✅ Done | Async, di-dispatch **di luar** DB transaction |
| `GET /api/v1/conversations` | ✅ Done | List conversations dengan unread count |
| `GET /api/v1/conversations/{id}/messages` | ✅ Done | Cursor-based pagination |
| `POST /api/v1/conversations/{id}/messages` | ✅ Done | Text & image |
| `POST /api/v1/conversations/{id}/read` | ✅ Done | Mark read |
| `GET /api/v1/conversations/{id}/media` | ✅ Done | Media gallery |
| FCM push notification | ✅ Done | Always-fire (Supabase Realtime belum aktif di FE) |

---

## Hal yang Harus Difix di FE (React Native)

| Issue | Detail |
|---|---|
| FCM Token Palsu | FE mengirim hardcode string bukan token asli dari `@react-native-firebase/messaging` |
| FCM Token saat OAuth | `fcm_token` harus disertakan di body saat `POST /auth/oauth/google/verify-token` |
| `profileId` vs `id` di swipe | FE harus pakai `profileId` (UUID) sebagai `:targetId`, bukan `id` (`card_xxxx`) |
| Supabase Realtime (future) | Belum ada listener di FE — saat ini bergantung FCM untuk real-time chat |

---

## Prioritas Next Steps Backend

| Prioritas | Task |
|---|---|
| 🔴 High | CON-64: RevenueCat webhook integration (`is_pro` flag) |
| 🟡 Medium | CON-65: Rewind — premium gate + restored card response |
| 🟡 Medium | `super_like` — premium enforcement |
| 🟢 Low | Supabase Realtime — aktifkan online/offline FCM branching setelah FE implement |
