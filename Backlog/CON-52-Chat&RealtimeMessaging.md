# [API Contract] Chat + Real-time Messaging

# Chat + Real-time Messaging API Contract

## Base URL: `/api/v1`

---

## 1\. Get Conversations

`GET /conversations`

Query: `page`, `limit`

Response 200:

```json
{
  "conversations": [{
    "id": "conv_abc",
    "match_id": "mtc_789",
    "other_user": {
      "user_id": "usr_123",
      "name": "Ardi Wijaya",
      "avatar_url": "https://...",
      "headline": "Full-Stack Engineer",
      "is_online": true
    },
    "last_message": {
      "id": "msg_999",
      "text": "That sounds interesting!",
      "sent_by": "usr_123",
      "sent_at": "2026-04-10T14:35:00Z",
      "is_read": false
    },
    "unread_count": 2,
    "created_at": "2026-04-10T08:00:00Z"
  }],
  "total": 3
}
```

---

## 2\. Get Messages

`GET /conversations/:conversation_id/messages`

Query: `limit` (default 50), `before` (cursor for infinite scroll up)

Response 200:

```json
{
  "messages": [
    {
      "id": "msg_001",
      "conversation_id": "conv_abc",
      "sender_id": "usr_123",
      "type": "text",
      "text": "Hey! I saw you're interested in fintech",
      "media": null,
      "sent_at": "2026-04-10T14:30:00Z",
      "read_at": "2026-04-10T14:31:00Z"
    },
    {
      "id": "msg_003",
      "sender_id": "usr_123",
      "type": "image",
      "text": null,
      "media": {
        "url": "https://cdn.../image.jpg",
        "thumbnail_url": "https://cdn.../thumb.jpg",
        "mime_type": "image/jpeg",
        "size_bytes": 245000
      },
      "sent_at": "2026-04-10T14:33:00Z"
    }
  ],
  "has_more": true,
  "next_cursor": "msg_000"
}
```

---

## 3\. Send Message

`POST /conversations/:conversation_id/messages`

Text: `{ "type": "text", "text": "Hello!" }`
Image: `{ "type": "image", "media_id": "upload_xyz" }`

Response 201: message object

---

## 4\. Mark as Read

`POST /conversations/:conversation_id/read`

Request: `{ "last_read_message_id": "msg_003" }`

---

## 5\. Upload Media

`POST /upload`

multipart/form-data, `file` field. Max 10MB. Types: image/jpeg, image/png, image/webp

Response 200:

```json
{ "media_id": "upload_xyz", "url": "https://...", "thumbnail_url": "https://...", "mime_type": "image/jpeg", "size_bytes": 245000 }
```

---

## 6\. Get Shared Media

`GET /conversations/:conversation_id/media`

Returns all media shared in a conversation.

---

## WebSocket

Connection: `wss://api.connectx.app/ws?token=<access_token>`

### Client → Server

| Event | Payload |
| -- | -- |
| `typing.start` | `{ conversation_id }` |
| `typing.stop` | `{ conversation_id }` |
| `presence.online` | `{}` |
| `presence.offline` | `{}` |

### Server → Client

| Event | Payload |
| -- | -- |
| `message.new` | `{ message object }` |
| `message.read` | `{ conversation_id, reader_id, last_read_message_id }` |
| `typing.indicator` | `{ conversation_id, user_id, is_typing }` |
| `presence.update` | `{ user_id, is_online }` |
| `match.created` | `{ match object }` |
| `match.expiring_soon` | `{ match_id, expires_in_hours }` |

FE should implement exponential backoff reconnection. On reconnect, fetch latest via REST to fill gaps.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-52/api-contract-chat-real-time-messaging](https://linear.app/summondev/issue/CON-52/api-contract-chat-real-time-messaging)
- Identifier: CON-52
- Status: Duplicate
- Priority: Urgent
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 4 - Connects + Chat (target: 2026-04-16T17:00:00.000Z)
- Due date: 2026-04-16T17:00:00.000Z
- Created: 2026-04-12T08:57:37.866Z
- Updated: 2026-04-12T09:12:44.827Z