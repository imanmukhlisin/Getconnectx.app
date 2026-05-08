# [API Contract] Settings + Account Management

# Settings + Account Management API Contract

## Base URL: `/api/v1`

---

## 1\. Get All Settings

`GET /settings`

Response 200:

```json
{
  "language": "en",
  "matching": {
    "industries": ["fintech", "saas"],
    "roles": ["cofounder", "team_member"],
    "availability": ["full_time", "flexible"],
    "locations": ["jakarta", "remote"],
    "distance_km": 50,
    "stages": ["mvp", "live"]
  },
  "notifications": {
    "push_enabled": true,
    "new_matches": true,
    "messages": true,
    "match_expiring": true,
    "team_invitations": true,
    "profile_views": false,
    "product_updates": true,
    "email_notifications": false
  },
  "privacy": {
    "profile_visibility": "public",
    "show_distance": true,
    "show_online_status": true,
    "read_receipts": true
  },
  "account": {
    "email": "user@example.com",
    "phone": "+62812****7890",
    "is_paused": false,
    "paused_at": null
  }
}
```

---

## 2\. Update Language

`PUT /settings/language`

Request: `{ "language": "id" }`
Values: `en`, `id`
Response 200: `{ "language": "id" }`

---

## 3\. Update Matching Preferences

`PUT /settings/matching`

Request (partial):

```json
{ "industries": ["fintech"], "distance_km": 100 }
```

Response 200: full matching object

---

## 4\. Update Notification Settings

`PUT /settings/notifications`

Request (partial): `{ "push_enabled": false, "messages": true }`
Response 200: full notifications object

---

## 5\. Update Privacy Settings

`PUT /settings/privacy`

Request (partial): `{ "profile_visibility": "matches_only", "show_online_status": false }`

Visibility values: `public`, `matches_only`, `hidden`
Response 200: full privacy object

---

## 6\. Update Email

`PUT /settings/email`

Request: `{ "new_email": "new@example.com", "password": "current_password" }`
Response 200: `{ "message": "Verification email sent to new address" }`

---

## 7\. Update Phone

`PUT /settings/phone`

Request: `{ "new_phone": "+6281999888777" }`
Response 200: `{ "message": "OTP sent to new phone" }`

---

## 8\. Change Password

`PUT /settings/password`

Request: `{ "current_password": "...", "new_password": "..." }`
Response 200: `{ "success": true }`
Errors: 401 `wrong_current_password`

---

## 9\. Pause Account (Temporary Off)

`POST /account/pause`

Response 200:

```json
{ "is_paused": true, "paused_at": "2026-04-12T10:00:00Z", "message": "Your profile is now hidden from discovery" }
```

Effect: profile hidden from feed, matching paused, existing matches/chats retained.

---

## 10\. Resume Account

`POST /account/resume`

Response 200: `{ "is_paused": false, "resumed_at": "..." }`

---

## 11\. Export Data

`POST /account/export`

Response 202: `{ "export_id": "exp_123", "status": "processing", "message": "You'll receive a download link via email" }`

`GET /account/export/:export_id`
Response 200: `{ "status": "ready", "download_url": "https://...", "expires_at": "..." }`

---

## 12\. Delete Account

`POST /account/delete`

Request: `{ "password": "current_password", "reason": "not_useful" }`

Reasons: `not_useful`, `found_someone`, `privacy_concerns`, `too_many_notifications`, `other`

Response 200:

```json
{ "status": "scheduled", "delete_at": "2026-04-26T10:00:00Z", "message": "Your account will be deleted in 14 days. Log in to cancel." }
```

Soft delete with 14-day grace period. Login during grace period cancels deletion.

---

## 13\. Cancel Deletion

`POST /account/cancel-deletion`

Response 200: `{ "status": "active", "message": "Account deletion cancelled" }`

## Metadata
- URL: [https://linear.app/summondev/issue/CON-55/api-contract-settings-account-management](https://linear.app/summondev/issue/CON-55/api-contract-settings-account-management)
- Identifier: CON-55
- Status: Duplicate
- Priority: High
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 6 - Profile & Settings (target: 2026-04-21T17:00:00.000Z)
- Due date: 2026-04-21T17:00:00.000Z
- Created: 2026-04-12T08:58:45.043Z
- Updated: 2026-04-12T09:12:50.429Z