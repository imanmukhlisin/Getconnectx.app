# 📖 ConnectX Backend – API Documentation (v1)

Dokumen ini berisi referensi lengkap semua endpoint API **ConnectX**, dibangun dengan **Laravel 11 + Supabase + Laravel Sanctum**.

---

## 🛠️ Setup & Headers

| Info | Value |
|:-----|:------|
| **Base URL (Docker)** | `http://localhost/api/v1` |
| **Base URL (Local)** | `http://localhost:8000/api/v1` |
| **Format** | JSON |

### Required Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}   (hanya untuk endpoint terproteksi)
```

> [!IMPORTANT]
> Selalu sertakan `Accept: application/json` agar Laravel mengembalikan error validasi dalam format JSON, bukan redirect HTML.

---

## 🔐 Authentication Flow

### 1. Registrasi Bertahap (Step-by-Step)

Registrasi dilakukan dalam **5 langkah berurutan**. Sistem menggunakan `registration_step` untuk melacak progres. Setiap response akan menyertakan `next_step` sebagai petunjuk langkah berikutnya.

---

#### ▶ Step 1 – Daftar Akun Baru

```
POST /auth/register
```

**Request Body:**
```json
{

    "email": "user@example.com",
    "password": "PasswordKuat123!",
    "password_confirmation": "PasswordKuat123!"
}
```


> Password wajib: min 8 karakter, huruf besar+kecil, angka, dan simbol.

**Response (201):**
```json
{
    "status": "success",
    "message": "Registration successful. Please verify your email.",
    "next_step": "NEED_EMAIL_OTP",
    "data": { "user": { ... } },
    "token": "1|xxxxxxx",
    "token_type": "Bearer"
}
```

---

#### ▶ Step 2 – Kirim OTP ke Email

```
POST /auth/email/send-otp
Authorization: Bearer {registration-token}
```

**Response (200):**
```json
{
    "status": "success",
    "message": "OTP Verification Code has been sent to user@example.com...",
    "next_step": "NEED_EMAIL_VERIFICATION"
}
```

---

#### ▶ Step 3 – Verifikasi Email

```
POST /auth/verify-email
Authorization: Bearer {registration-token}
```

**Request Body:**
```json
{
    "otp_code": "123456"
}
```

**Response (200):**
```json
{
    "status": "success",
    "message": "Email verified successfully.",
    "next_step": "NEED_WHATSAPP_VERIFICATION",
    "data": { "user": { ... } }
}
```

---

#### ▶ Step 4 – Kirim OTP ke WhatsApp

```
POST /auth/whatsapp/send-otp
Authorization: Bearer {registration-token}
```

**Request Body:**
```json
{
    "whatsapp_number": "+6281234567890"
}
```

**Response (200):**
```json
{
    "status": "success",
    "message": "OTP Verification Code has been sent to WhatsApp +6281234567890...",
    "next_step": "NEED_WHATSAPP_VERIFICATION"
}
```

---

#### ▶ Step 5 – Verifikasi WhatsApp (Finalize Registrasi)

```
POST /auth/verify-whatsapp
Authorization: Bearer {registration-token}
```

**Request Body:**
```json
{
    "otp_code": "123456"
}
```

**Response (200):**
```json
{
    "status": "success",
    "message": "Registration complete! Welcome to ConnectX.",
    "next_step": "REGISTRATION_COMPLETE",
    "data": { "user": { ... } },
    "token": "2|xxxxxxx",
    "token_type": "Bearer",
    "supabase_token": "eyJhbGciOiJIUzI1NiI..." 
}
```
> Token yang dihasilkan di Step 5 adalah **Full-Access Token** (Sanctum) yang bisa digunakan untuk semua endpoint terproteksi API Laravel.
> **supabase_token** adalah JWT khusus yang digunakan oleh Frontend untuk mengakses SDK Supabase (Realtime/RLS).

---

### 2. Login

#### 🔑 Metode A – Login dengan Password (Tradisional)

```
POST /auth/login/password
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "PasswordKuat123!"
}
```

**Response Sukses (200):**
```json
{
    "status": "success",
    "message": "Login successful! Welcome back.",
    "next_step": "LOGIN_SUCCESS",
    "data": { "user": { ... } },
    "token": "3|xxxxxxx",
    "token_type": "Bearer",
    "supabase_token": "eyJhbGciOiJIUzI1NiI..."
}
```

**Response Gagal – Password Salah (401):**
```json
{
    "status": "error",
    "message": "Invalid email or password. Please try again.",
    "code": "INVALID_CREDENTIALS"
}
```

**Response Gagal – Akun Belum Aktif (403):**
```json
{
    "status": "error",
    "message": "Your account is not active. Please complete the registration or verification process.",
    "code": "INACTIVE_USER"
}
```

---

#### 🔑 Metode B – Login dengan OTP (Passwordless)

**Step B.1 – Request OTP:**
```
POST /auth/login/otp/send
```
```json
{ "email": "user@example.com" }
```

**Step B.2 – Verifikasi OTP:**
```
POST /auth/login/otp/verify
```
```json
{
    "email": "user@example.com",
    "otp_code": "123456"
}
```

---

### 3. OAuth (Social Login)

| Endpoint | Keterangan |
|:---------|:-----------|
| `GET /auth/oauth/google` | Redirect ke Google Login |
| `GET /auth/oauth/apple` | Redirect ke Apple Login |
| `GET /auth/oauth/linkedin` | Redirect ke LinkedIn Login |

Callback ditangani otomatis oleh server setelah proses OAuth selesai.

---

## 📊 Registration Step Values

| Value | Status |
|:------|:-------|
| `1` | `STEP_REGISTERED` – Akun dibuat |
| `2` | `STEP_EMAIL_OTP_SENT` – OTP email terkirim |
| `3` | `STEP_EMAIL_VERIFIED` – Email terverifikasi |
| `4` | `STEP_WHATSAPP_OTP_SENT` – OTP WA terkirim |
| `5` | `STEP_WHATSAPP_VERIFIED` – Registrasi selesai |

---

## ❌ Error Codes

| HTTP Code | Code | Keterangan |
|:----------|:-----|:-----------|
| `401` | `INVALID_CREDENTIALS` | Email/password salah |
| `403` | `INACTIVE_USER` | Akun belum aktif |
| `403` | `REGISTRATION_STEP_LOCKED` | Step belum memenuhi syarat |
| `409` | `EMAIL_ALREADY_VERIFIED` | Email sudah diverifikasi |
| `409` | `WHATSAPP_ALREADY_VERIFIED` | WA sudah diverifikasi |
| `422` | _(field errors)_ | Validasi gagal |
| `429` | `TOO_MANY_REQUESTS` | Rate limit terlampaui |
| `502` | `WHATSAPP_DELIVERY_FAILED` | Gagal kirim WA via Fonnte |

---

## 🐳 Perintah Docker

```bash
# Build & jalankan semua container
wsl docker compose up -d --build

# Masuk ke bash container
wsl docker exec -it getconnectx-app bash

# Cek status migrasi Supabase
wsl docker exec getconnectx-app php artisan migrate:status

# Reset konfigurasi
wsl docker exec getconnectx-app php artisan config:clear
```

---

## 🔗 Layanan
| Layanan | URL |
|:--------|:----|
| **API (Nginx)** | http://localhost |
| **Mailpit (Email Testing)** | http://localhost:8025 |
| **Chat Interface (Web)** | http://localhost/chat |

---

## 📸 Media & Files (GCS Direct Upload)

Sistem menggunakan **Direct-to-Cloud Upload** untuk efisiensi. Backend hanya memberikan "Tiket" (Pre-signed URL), lalu Frontend mengupload file langsung ke Google Cloud Storage.

#### ▶ Request Upload URL
```
POST /media/upload-url
Authorization: Bearer {token}
```
**Request Body:**
```json
{
    "file_name": "profile.jpg",
    "content_type": "image/jpeg"
}
```
**Response (200):**
```json
{
    "status": "success",
    "data": {
        "upload_url": "https://storage.googleapis.com/...",
        "file_path": "uploads/a1868ae5.../profile.jpg",
        "expires_at": "2026-04-12T..."
    }
}
```
> **Cara Pakai:** Frontend melakukan `PUT` request ke `upload_url` dengan body berupa file binary dan header `Content-Type` yang sesuai. Setelah sukses, simpan `file_path` untuk dikirim ke API Update Profile.

---

## 💬 Conversation System (Real-time)

Sistem chat ConnectX menggunakan **Supabase Realtime (Broadcast)** untuk pengiriman pesan instan.

### 1. Daftar Percakapan (Conversations)

#### ▶ Ambil Daftar Percakapan
```
GET /conversations
Authorization: Bearer {token}
```
**Response (200):** Menampilkan daftar percakapan aktif.

#### ▶ Mulai Percakapan Baru
```
POST /conversations
Authorization: Bearer {token}
```
**Request Body:**
```json
{ "user_id": "{uuid-target-user}" }
```

---

### 2. Pesan & Media (Messages)

#### ▶ Kirim Pesan Teks
```
POST /conversations/{conversation_id}/messages
Authorization: Bearer {token}
```
**Request Body:**
```json
{ "content": "Halo!", "type": "text" }
```

#### ▶ Kirim Media (Gambar)
```
POST /conversations/{conversation_id}/media
Authorization: Bearer {token}
```
**Form Data:** `file` (image)

#### ▶ Signal Typing (Heartbeat)
```
POST /conversations/{conversation_id}/typing
Authorization: Bearer {token}
```

#### ▶ Ambil Riwayat Pesan
```
GET /conversations/{conversation_id}/messages
```

---

## 📋 Dynamic Onboarding Engine

Digunakan setelah verifikasi inti selesai untuk melengkapi profil user secara dinamis.

#### ▶ Start Onboarding Session
```
POST /onboarding/sessions
```

#### ▶ Kirim Jawaban
```
POST /onboarding/sessions/{session_id}/answer
```
**Body:** `{ "step_id": "...", "answers": { "field": "value" } }`

---

### ⚡ Integrasi Real-time (Supabase)

Untuk aplikasi (Frontend/Mobile) agar bisa menerima pesan secara instan (real-time):

1.  Gunakan **Supabase JS Client**.
2.  Inisialisasi session menggunakan `supabase_token` yang didapat dari API Login:
    ```javascript
    await supabase.auth.setSession({
      access_token: response.supabase_token,
      refresh_token: response.supabase_token
    });
    ```
3.  Subscribe ke channel: `chat_{conversation_id}`.
4.  Listen untuk event: `message`.

Contoh (JS):
```javascript
const channel = supabase.channel(`chat_${id}`)
  .on('broadcast', { event: 'message' }, ({ payload }) => {
    console.log("Pesan baru masuk:", payload);
  })
  .subscribe()
```
