# 📨 Implementasi Messaging API — ConnectX

> **Base URL Production:** `https://getconnectxapp.vercel.app/api/v1`  
> **Auth:** Semua endpoint butuh header `Authorization: Bearer {token}` (Sanctum)  
> **Realtime:** Dihandle oleh **Supabase Realtime** (Postgres Changes). FE subscribe ke table `messages` untuk event INSERT.  
> **FCM:** Dikirim otomatis jika target user **offline** (last API activity > 3 menit lalu via `personal_access_tokens.last_used_at`).

---

## Tabel Skema yang Diperbarui

### `messages`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | UUID | Primary key |
| `conversation_id` | UUID (nullable) | Nullable untuk temporary upload record |
| `sender_id` | UUID | FK → users |
| `content` | text (nullable) | Isi teks pesan |
| `type` | string | `text` atau `image` |
| `media` | jsonb (nullable) | Payload media: url, thumbnail, mime, size |
| `read_at` | timestamp (nullable) | Waktu pesan dibaca |
| `is_read` | boolean | Legacy flag |

### `conversation_participants`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `conversation_id` | UUID | FK → conversations |
| `user_id` | UUID | FK → users |
| `last_read_message_id` | UUID (nullable) | ID pesan terakhir yang dibaca user ini |

---

## Endpoint 1 — List Conversations

### `GET /conversations`

**Query Params:**
| Param | Default | Keterangan |
|---|---|---|
| `page` | 1 | Halaman |
| `limit` | 20 | Max 50 per page |

**Postman Setup:**
```
GET https://getconnectxapp.vercel.app/api/v1/conversations?page=1&limit=20
Authorization: Bearer {{token}}
```

**Response 200:**
```json
{
  "conversations": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "match_id": null,
      "other_user": {
        "user_id": "550e8400-e29b-41d4-a716-446655440002",
        "name": "Ardi Wijaya",
        "avatar_url": "https://storage.googleapis.com/bucket/avatars/ardi.jpg",
        "headline": "Full-Stack Engineer at Tokopedia",
        "is_online": true
      },
      "last_message": {
        "id": "msg-uuid-999",
        "text": "That sounds really interesting!",
        "sent_by": "550e8400-e29b-41d4-a716-446655440002",
        "sent_at": "2026-05-03T14:35:00Z",
        "is_read": false
      },
      "unread_count": 3,
      "created_at": "2026-05-03T08:00:00Z"
    }
  ],
  "total": 1
}
```

> **`is_online`**: `true` jika token Sanctum lawan bicara dipakai dalam 3 menit terakhir.

---

## Endpoint 2 — Riwayat Pesan (Cursor-Based)

### `GET /conversations/{conversation_id}/messages`

**Query Params:**
| Param | Default | Keterangan |
|---|---|---|
| `limit` | 50 | Max 100 |
| `before` | *(kosong)* | UUID message sebagai cursor untuk infinite scroll ke atas |

**Postman Setup (Load awal):**
```
GET https://getconnectxapp.vercel.app/api/v1/conversations/550e8400-e29b-41d4-a716-446655440001/messages?limit=50
Authorization: Bearer {{token}}
```

**Postman Setup (Load lebih lama / scroll ke atas):**
```
GET https://getconnectxapp.vercel.app/api/v1/conversations/550e8400-e29b-41d4-a716-446655440001/messages?limit=50&before=msg-uuid-001
Authorization: Bearer {{token}}
```

**Response 200:**
```json
{
  "messages": [
    {
      "id": "msg-uuid-001",
      "conversation_id": "550e8400-e29b-41d4-a716-446655440001",
      "sender_id": "550e8400-e29b-41d4-a716-446655440002",
      "type": "text",
      "text": "Hey! I saw you're interested in fintech",
      "media": null,
      "sent_at": "2026-05-03T14:30:00Z",
      "read_at": "2026-05-03T14:31:00Z"
    },
    {
      "id": "msg-uuid-002",
      "conversation_id": "550e8400-e29b-41d4-a716-446655440001",
      "sender_id": "550e8400-e29b-41d4-a716-446655440003",
      "type": "image",
      "text": null,
      "media": {
        "url": "https://storage.googleapis.com/bucket/chat-media/uuid.jpg",
        "thumbnail_url": "https://storage.googleapis.com/bucket/chat-media/uuid.jpg",
        "mime_type": "image/jpeg",
        "size_bytes": 245000
      },
      "sent_at": "2026-05-03T14:33:00Z",
      "read_at": null
    }
  ],
  "has_more": true,
  "next_cursor": "msg-uuid-000"
}
```

> **Cara pakai `next_cursor`:** Ambil nilai `next_cursor` dari respons, kirim ulang sebagai `?before=<next_cursor>` untuk halaman sebelumnya.

---

## Endpoint 3 — Kirim Pesan Teks

### `POST /conversations/{conversation_id}/messages`

**Postman Setup:**
```
POST https://getconnectxapp.vercel.app/api/v1/conversations/550e8400-e29b-41d4-a716-446655440001/messages
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "text",
  "text": "Hello! I love your startup idea 🚀"
}
```

**Response 201:**
```json
{
  "id": "msg-uuid-new",
  "conversation_id": "550e8400-e29b-41d4-a716-446655440001",
  "sender_id": "550e8400-e29b-41d4-a716-446655440003",
  "type": "text",
  "text": "Hello! I love your startup idea 🚀",
  "media": null,
  "sent_at": "2026-05-03T15:00:00Z",
  "read_at": null
}
```

---

## Endpoint 3b — Kirim Pesan Gambar

**Langkah 1: Upload gambar dulu**
```
POST https://getconnectxapp.vercel.app/api/v1/upload
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

file: [pilih file .jpg/.png/.webp, max 10MB]
```

**Response Upload:**
```json
{
  "media_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "url": "https://storage.googleapis.com/bucket/chat-media/a1b2c3d4.jpg",
  "thumbnail_url": "https://storage.googleapis.com/bucket/chat-media/a1b2c3d4.jpg",
  "mime_type": "image/jpeg",
  "size_bytes": 245000
}
```

**Langkah 2: Kirim pesan dengan media_id**
```
POST https://getconnectxapp.vercel.app/api/v1/conversations/550e8400-e29b-41d4-a716-446655440001/messages
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "type": "image",
  "media_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

**Response 201:**
```json
{
  "id": "msg-uuid-new2",
  "conversation_id": "550e8400-e29b-41d4-a716-446655440001",
  "sender_id": "550e8400-e29b-41d4-a716-446655440003",
  "type": "image",
  "text": null,
  "media": {
    "url": "https://storage.googleapis.com/bucket/chat-media/a1b2c3d4.jpg",
    "thumbnail_url": "https://storage.googleapis.com/bucket/chat-media/a1b2c3d4.jpg",
    "mime_type": "image/jpeg",
    "size_bytes": 245000
  },
  "sent_at": "2026-05-03T15:01:00Z",
  "read_at": null
}
```

---

## Endpoint 4 — Tandai Sudah Dibaca

### `POST /conversations/{conversation_id}/read`

**Postman Setup:**
```
POST https://getconnectxapp.vercel.app/api/v1/conversations/550e8400-e29b-41d4-a716-446655440001/read
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "last_read_message_id": "msg-uuid-001"
}
```

**Response 200:**
```json
{
  "status": "ok"
}
```

> Endpoint ini meng-update `last_read_message_id` di `conversation_participants` dan menstempel `read_at` ke semua pesan yang dikirim lawan bicara sampai dengan pesan tersebut.

---

## Endpoint 5 — Galeri Media

### `GET /conversations/{conversation_id}/media`

**Postman Setup:**
```
GET https://getconnectxapp.vercel.app/api/v1/conversations/550e8400-e29b-41d4-a716-446655440001/media?limit=30
Authorization: Bearer {{token}}
```

**Response 200:**
```json
{
  "media": [
    {
      "message_id": "msg-uuid-002",
      "sender_id": "550e8400-e29b-41d4-a716-446655440002",
      "media": {
        "url": "https://storage.googleapis.com/bucket/chat-media/a1b2c3d4.jpg",
        "thumbnail_url": "https://storage.googleapis.com/bucket/chat-media/a1b2c3d4.jpg",
        "mime_type": "image/jpeg",
        "size_bytes": 245000
      },
      "sent_at": "2026-05-03T14:33:00Z"
    }
  ]
}
```

---

## FCM & Supabase Realtime Logic

### Kapan FCM Ditembak?
```
POST /conversations/{id}/messages dipanggil
        │
        ▼
Cek personal_access_tokens.last_used_at milik target user
        │
        ├─── last_used_at < 3 menit → ONLINE
        │         └─→ Skip FCM (Supabase Realtime yang handle)
        │
        └─── last_used_at > 3 menit / tidak ada token → OFFLINE
                  └─→ Tembak FCM Push Notification
                        Template: "new_message"
                        Title: "💬 New message from [sender_name]"
                        Body: "[message]"
```

### Supabase Realtime (Setup FE)
FE cukup subscribe ke table `messages` dengan filter `conversation_id`:
```javascript
const channel = supabase
  .channel('chat-room')
  .on('postgres_changes', {
    event: 'INSERT',
    schema: 'public',
    table: 'messages',
    filter: `conversation_id=eq.${conversationId}`
  }, (payload) => {
    // Render pesan baru di chat
    appendMessage(payload.new);
  })
  .subscribe();
```

### Notification Templates yang Tersedia
| Template Name | Trigger |
|---|---|
| `new_message` | Pesan baru diterima saat user offline |
| `new_match` | Ada mutual match (swipe kanan keduanya) |
| `account_created` | Registrasi berhasil |

---

## Error Responses

| HTTP Code | Kondisi |
|---|---|
| `401` | Token tidak ada atau kadaluarsa |
| `403` | User bukan peserta conversation |
| `422` | Validasi gagal (field wajib kosong, tipe salah, dll) |
| `500` | Upload file gagal / error server |
