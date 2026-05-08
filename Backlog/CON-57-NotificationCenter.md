# [API Contract] Notifications Center

# Notifications Center API Contract

## Base URL: `/api/v1`

---

## 1\. Get Notifications

`GET /notifications`

Query: `page`, `limit` (default 20)

Response 200:

```json
{
  "unread_count": 5,
  "notifications": [
    {
      "id": "notif_001",
      "type": "match",
      "title": "You matched with Ardi Wijaya!",
      "body": "Send a message to start the conversation",
      "icon": "match",
      "data": { "match_id": "mtc_789", "user_id": "usr_123" },
      "navigate_to": "/chat/conv_abc",
      "is_read": false,
      "created_at": "2026-04-12T08:00:00Z"
    },
    {
      "id": "notif_002",
      "type": "message",
      "title": "Ardi Wijaya sent you a message",
      "body": "Hey! I saw you're interested in...",
      "icon": "chat",
      "data": { "conversation_id": "conv_abc" },
      "navigate_to": "/chat/conv_abc",
      "is_read": false,
      "created_at": "2026-04-12T08:05:00Z"
    },
    {
      "id": "notif_003",
      "type": "team",
      "title": "You've been added to ConnectX",
      "body": "John added you as CTO",
      "icon": "team",
      "data": { "team_id": "team_abc" },
      "navigate_to": "/team",
      "is_read": true,
      "created_at": "2026-04-11T10:00:00Z"
    }
  ],
  "has_next": true
}
```

Notification types: `match`, `message`, `team`, `invite`, `match_expiring`, `match_expired`, `application_update`, `profile_view` (PRO), `system`

FE groups by date (Today, Yesterday, Earlier).

---

## 2\. Mark as Read

`POST /notifications/read`

Single: `{ "notification_id": "notif_001" }`
All: `{ "mark_all": true }`

Response 200: `{ "unread_count": 0 }`

---

## 3\. Get Unread Count

`GET /notifications/unread-count`

Response 200: `{ "count": 5 }`

Lightweight endpoint for badge updates. FE polls this or uses WebSocket.

---

## WebSocket Events

| Event | Payload |
| -- | -- |
| `notification.new` | `{ notification object }` |
| `notification.count` | `{ count: 5 }` |

FE should update bell badge on `notification.count` events.

---

## Push Notification Payload (FCM)

```json
{
  "notification": {
    "title": "You matched with Ardi!",
    "body": "Send a message to start the conversation"
  },
  "data": {
    "type": "match",
    "navigate_to": "/chat/conv_abc",
    "notification_id": "notif_001"
  }
}
```

FE handles tap → navigate to `data.navigate_to`.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-57/api-contract-notifications-center](https://linear.app/summondev/issue/CON-57/api-contract-notifications-center)
- Identifier: CON-57
- Status: Duplicate
- Priority: Medium
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 6 - Profile & Settings (target: 2026-04-21T17:00:00.000Z)
- Due date: 2026-04-21T17:00:00.000Z
- Created: 2026-04-12T08:59:21.350Z
- Updated: 2026-04-12T09:12:54.100Z