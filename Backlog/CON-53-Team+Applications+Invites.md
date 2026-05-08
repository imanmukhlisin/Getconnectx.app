# [API Contract] Team + Applications + Invites

# Team + Applications + Invites API Contract

## Base URL: `/api/v1`

---

## 1\. Add to Team

`POST /teams/members`

Triggered from chat "Add to Team" button.

Request:

```json
{
  "user_id": "usr_123",
  "role": "cto",
  "equity_percentage": 15,
  "commitment": "full_time"
}
```

Role values: `cofounder`, `cto`, `engineer`, `product_manager`, `designer`, `marketing`, `operations`
Commitment: `full_time`, `part_time`, `advisor`

Response 201:

```json
{
  "member_id": "mem_abc",
  "user": { "user_id": "usr_123", "name": "Ardi Wijaya", "avatar_url": "..." },
  "role": "cto",
  "role_label": "CTO",
  "equity_percentage": 15,
  "commitment": "full_time",
  "status": "pending",
  "added_at": "2026-04-12T10:00:00Z"
}
```

---

## 2\. Get Team Roster

`GET /teams`

Response 200:

```json
{
  "team_name": "ConnectX",
  "members": [{
    "member_id": "mem_abc",
    "user": { "user_id": "usr_123", "name": "Ardi Wijaya", "avatar_url": "...", "headline": "Full-Stack Engineer" },
    "role": "cto",
    "role_label": "CTO",
    "equity_percentage": 15,
    "commitment": "full_time",
    "status": "active",
    "added_at": "2026-04-10T10:00:00Z"
  }],
  "total_equity_allocated": 30,
  "total_members": 3
}
```

Status values: `active`, `pending`, `invited`

---

## 3\. Edit Team Member

`PUT /teams/members/:member_id`

Request: `{ "role": "engineer", "equity_percentage": 10, "commitment": "part_time" }`
Response 200: updated member object

---

## 4\. Remove Team Member

`DELETE /teams/members/:member_id`

Response 200: `{ "removed": true }`

---

## 5\. Get My Applications (Co-Founder view)

`GET /applications`

Response 200:

```json
{
  "stats": { "applied": 5, "in_review": 2, "interviews": 1 },
  "applications": [{
    "id": "app_123",
    "startup": { "startup_id": "stp_456", "name": "ConnectX", "logo_url": "..." },
    "role_applied": "CTO",
    "status": "in_review",
    "applied_at": "2026-04-08T10:00:00Z"
  }]
}
```

Status: `applied`, `in_review`, `interview`, `accepted`, `rejected`

---

## 6\. Send Team Invite

`POST /invites`

Request: `{ "user_id": "usr_789", "role": "engineer", "message": "Would love to have you on the team!" }`

Response 201:

```json
{ "invite_id": "inv_abc", "status": "pending", "sent_at": "..." }
```

---

## 7\. Respond to Invite

`POST /invites/:invite_id/accept`
Response 200: `{ "invite_id": "inv_abc", "status": "accepted" }`

`POST /invites/:invite_id/decline`
Response 200: `{ "invite_id": "inv_abc", "status": "declined" }`

---

## 8\. Revoke Invite

`DELETE /invites/:invite_id`
Response 200: `{ "revoked": true }`

---

## 9\. Get Pending Invites

`GET /invites`

Query: `type` (`sent` / `received`)

Response 200:

```json
{
  "sent": [{ "invite_id": "inv_abc", "user": {...}, "role": "engineer", "status": "pending", "sent_at": "..." }],
  "received": [{ "invite_id": "inv_def", "from_user": {...}, "startup": {...}, "role": "cto", "message": "...", "sent_at": "..." }]
}
```

---

## Push Notifications

| Trigger | Title | Body |
| -- | -- | -- |
| Added to team | "You've been added to a team!" | "\[Name\] added you as \[Role\]" |
| Invite received | "Team invitation" | "\[Name\] invited you to join as \[Role\]" |
| Invite accepted | "Invite accepted!" | "\[Name\] accepted your invite" |
| Application status | "Application update" | "Your application to \[Startup\] is now \[Status\]" |


