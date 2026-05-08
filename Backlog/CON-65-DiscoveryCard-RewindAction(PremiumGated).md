# Discovery Card - Rewind Action (Premium Gated)

```md
### 5. Rewind endpoint
`POST /api/v1/discovery/swipes/rewind`
Rewind is a premium-only discovery action. Backend is the source of truth for whether rewind is allowed and which last swipe can be restored.
#### Rules
- rewind is available only to premium users
- rewind restores the most recent rewindable swipe for the current user
- frontend may animate card restoration locally, but backend must confirm the undo
- backend must return the restored discovery card payload on success
#### `200 OK`
```json

{

  "success": true,

  "message": "Last swipe rewound.",

  "data": {

    "profileId": "prof_123",
    "rewoundAction": "pass",
    "card": {
      "id": "card_123",
      "profileId": "prof_123",
      "photoUrl": "https://example.com/photo.jpg",
      "name": "Ardi Wijaya",
      "age": 29,
      "headline": "Founding Engineer",
      "location": {
        "city": "Jakarta",
        "country": "Indonesia",
        "display": "Jakarta, Indonesia"
      },
      "match": {
        "score": 88
      },
      "badges": [],
      "interests": [],
      "skills": []
    }
  }

}
```

Required success fields:

* `profileId`
* `rewoundAction`
* `card`

#### `403 Forbidden` premium required

```json

{

  "success": false,

  "message": "ConnectX Pro is required to rewind your last swipe.",

  "error": {

    "code": "DISCOVERY_REWIND_PREMIUM_REQUIRED",
    "details": {
      "requiredEntitlement": "connectx_pro"
    }
  }

}
```

Behavior:

* frontend should open the premium paywall
* no rewind is applied until backend confirms success

#### `409 Conflict` no rewind available

```json

{

  "success": false,

  "message": "No swipe is available to rewind right now.",

  "error": {

    "code": "DISCOVERY_REWIND_NOT_AVAILABLE",
    "details": {
      "profileId": null,
      "rewoundAction": null,
      "reason": "EMPTY_HISTORY"
    }
  }

}
```

Recommended `reason` values:

* `EMPTY_HISTORY`
* `ALREADY_REWOUND`
* `WINDOW_EXPIRED`

## Metadata
- URL: [https://linear.app/summondev/issue/CON-65/discovery-card-rewind-action-premium-gated](https://linear.app/summondev/issue/CON-65/discovery-card-rewind-action-premium-gated)
- Identifier: CON-65
- Status: Backlog
- Priority: No priority
- Assignee: Unassigned
- Labels: API Contract, Backend
- Related issues: CON-60
- Created: 2026-04-14T07:44:07.626Z
- Updated: 2026-04-14T07:46:01.899Z

---

## Backend Implementation Status
**Status: ⚠️ Partial**

| Sub-feature | Status | Catatan |
|---|---|---|
| `POST /api/v1/discovery/swipes/rewind` | ⚠️ Partial | Route ada, controller method ada (`rewind`), perlu verifikasi logic rewind & premium gate |
| Return restored card payload | ❌ Not Yet | Response rewind belum include full card data |
| `DISCOVERY_REWIND_PREMIUM_REQUIRED` (403) | ❌ Not Yet | Premium gate belum diimplementasi |
| `DISCOVERY_REWIND_NOT_AVAILABLE` (409) | ❌ Not Yet | Reason codes belum diimplementasi |