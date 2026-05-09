# [API Contract] Feed + Swiping + Matching

# Feed + Swiping + Matching API Contract

## Base URL: `/api/v1`

---

## 1\. Get Feed

`GET /feed`

Query params: `type` (person/startup), `tab` (cofounder/team), `industry[]`, `role[]`, `availability[]`, `location[]`, `stage[]`, `distance_km`, `page`, `limit` (default 10, max 20)

Response 200:

```json
{
  "cards": [{
    "id": "card_usr123",
    "type": "person",
    "user_id": "usr_123",
    "name": "Ardi Wijaya",
    "age": 28,
    "avatar_url": "https://...",
    "location": "Jakarta, Indonesia",
    "distance_km": 3,
    "match_percentage": 98,
    "match_label": "Perfect Match",
    "headline": "Full-Stack Engineer",
    "looking_for": "co-founder",
    "stage": "mvp",
    "bio": "Building the future of...",
    "startup_idea": { "title": "FinPay", "description": "..." },
    "industries": ["fintech", "saas"],
    "skills": ["React Native", "Node.js"],
    "availability": "full_time",
    "languages": ["English", "Bahasa Indonesia"],
    "cofounder_type": "technical"
  }],
  "pagination": { "page": 1, "limit": 10, "total": 45, "has_next": true },
  "filters_applied": { "industry": ["fintech"], "tab": "cofounder" }
}
```

Startup card fields: `startup_id`, `name`, `logo_url`, `founded_by`, `stage`, `match_percentage`, `industries`, `team_size`, `description`, `open_roles`, `looking_for`, `availability`, `location`

Empty state: `{ "cards": [], "empty_state": { "title": "No more profiles", "subtitle": "Adjust filters or check back later", "cta": { "label": "Edit Filters", "action": "open_filters" } } }`

---

## 2\. Swipe Connect

`POST /swipe/connect`

Request: `{ "card_id": "card_usr123", "target_user_id": "usr_123" }`

Response 200 (no match): `{ "swiped": true, "is_match": false }`

Response 200 (match!):

```json
{
  "swiped": true,
  "is_match": true,
  "match": {
    "match_id": "mtc_789",
    "matched_with": { "user_id": "usr_123", "name": "Ardi Wijaya", "avatar_url": "..." },
    "match_type": "user_to_user",
    "match_message": "You're connected",
    "expires_at": "2026-04-18T10:00:00Z",
    "conversation_id": "conv_abc"
  }
}
```

`match_type`: `user_to_user` = "You're connected" | `user_to_startup` = "Connection started"

---

## 3\. Swipe Skip

`POST /swipe/skip`

Request: `{ "card_id": "card_usr123", "target_user_id": "usr_123" }`
Response 200: `{ "skipped": true }`

---

## 4\. Get Filter Options

`GET /feed/filters`

Response 200: `{ "industries": [{ "value": "saas", "label": "SaaS" }], "roles": [...], "availability": [...], "locations": [...], "stages": [...] }`

---

## 5\. Rewind (PRO)

`POST /swipe/rewind`

Response 200: `{ "rewound": true, "card": { ... } }`
Errors: 403 `pro_required` | 400 `no_swipe_to_rewind`

---

## Matching Algorithm

Score: industry overlap (0-25) + role compatibility (0-25) + skills match (0-20) + location proximity (0-15) + availability match (0-15). Cards sorted by score desc.

## WebSocket

`match.created` → `{ match_id, matched_with, match_type, match_message }`

## Metadata
- URL: [https://linear.app/summondev/issue/CON-50/api-contract-feed-swiping-matching](https://linear.app/summondev/issue/CON-50/api-contract-feed-swiping-matching)
- Identifier: CON-50
- Status: Duplicate
- Priority: Urgent
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 3 - Discovery & Matching (target: 2026-04-14T17:00:00.000Z)
- Due date: 2026-04-14T17:00:00.000Z
- Created: 2026-04-12T08:56:31.690Z
- Updated: 2026-04-12T09:12:40.692Z