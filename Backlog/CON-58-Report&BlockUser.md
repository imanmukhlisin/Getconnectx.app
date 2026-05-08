# [API Contract] Report / Block User

# Report / Block User API Contract

## Base URL: `/api/v1`

---

## 1\. Report User

`POST /report`

Request:

```json
{
  "reported_user_id": "usr_456",
  "reason": "fake_profile",
  "description": "This profile seems to be using someone else's photos"
}
```

Reason values: `spam`, `inappropriate_content`, `fake_profile`, `harassment`, `other`

Response 201:

```json
{
  "report_id": "rpt_abc",
  "status": "submitted",
  "message": "Thank you for reporting. We'll review this within 24 hours."
}
```

---

## 2\. Block User

`POST /block/:user_id`

Response 200:

```json
{
  "blocked": true,
  "user_id": "usr_456",
  "message": "User blocked. They won't be able to see your profile or contact you."
}
```

Side effects:

* User removed from feed
* Existing match cancelled
* Conversation hidden (not deleted)
* User can't see your profile

---

## 3\. Unblock User

`DELETE /block/:user_id`

Response 200: `{ "unblocked": true, "user_id": "usr_456" }`

---

## 4\. Get Blocked Users

`GET /blocked`

Response 200:

```json
{
  "blocked_users": [
    {
      "user_id": "usr_456",
      "name": "Blocked User",
      "avatar_url": "https://...",
      "blocked_at": "2026-04-10T10:00:00Z"
    }
  ]
}
```

---

## Notes for BE

* Reports go to admin moderation queue
* Multiple reports on same user should trigger auto-review
* Blocked users are excluded from feed query
* Block is bidirectional (neither side sees the other)

## Metadata
- URL: [https://linear.app/summondev/issue/CON-58/api-contract-report-block-user](https://linear.app/summondev/issue/CON-58/api-contract-report-block-user)
- Identifier: CON-58
- Status: Duplicate
- Priority: Medium
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 7 - Launch Ready (target: 2026-04-24T17:00:00.000Z)
- Due date: 2026-04-23T17:00:00.000Z
- Created: 2026-04-12T08:59:29.348Z
- Updated: 2026-04-12T09:12:55.907Z