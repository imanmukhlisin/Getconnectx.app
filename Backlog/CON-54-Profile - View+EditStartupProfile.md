# [API Contract] Profile - View, Edit, Startup Profile

# Profile API Contract

## Base URL: `/api/v1`

---

## 1\. Get My Profile

`GET /profile/me`

Response 200:

```json
{
  "user_id": "usr_abc",
  "first_name": "John",
  "last_name": "Carter",
  "avatar_url": "https://...",
  "title": "Startup Founder",
  "location": "Jakarta, Indonesia",
  "bio": "Building the future of...",
  "stats": { "connections": 24, "teams_joined": 2, "matches": 15 },
  "badges": ["Startup Founder", "Top Builder", "YC Alumni"],
  "startup_idea": { "title": "FinPay", "description": "..." },
  "personality_hobbies": ["Coffee Lover", "Basketball", "Hiking"],
  "skills": ["React Native", "Node.js", "Product Management"],
  "interests": ["Fintech", "SaaS", "AI"],
  "experience": [{ "id": "exp_1", "company": "Gojek", "role": "Engineer", "start_date": "2022-01", "end_date": "2024-06", "description": "..." }],
  "education": [{ "id": "edu_1", "school": "UI", "degree": "CS", "year": "2022" }],
  "languages": ["English", "Bahasa Indonesia"],
  "linkedin_url": "https://linkedin.com/in/...",
  "linkedin_connected": true
}
```

---

## 2\. Update Profile

`PUT /profile/me`

Request (partial update, only send changed fields):

```json
{
  "first_name": "John",
  "title": "Startup Founder",
  "bio": "Updated bio...",
  "skills": ["React Native", "Flutter"],
  "personality_hobbies": ["Coffee Lover"]
}
```

Response 200: full profile object

---

## 3\. Upload Avatar

`POST /profile/me/avatar`

multipart/form-data, `file` field. Max 5MB. Types: image/jpeg, image/png

Response 200: `{ "avatar_url": "https://cdn.../new-avatar.jpg" }`

---

## 4\. Add/Edit/Remove Experience

`POST /profile/me/experience` → create
`PUT /profile/me/experience/:id` → update
`DELETE /profile/me/experience/:id` → remove

Request: `{ "company": "Gojek", "role": "Engineer", "start_date": "2022-01", "end_date": "2024-06", "description": "..." }`

---

## 5\. Add/Edit/Remove Education

`POST /profile/me/education`
`PUT /profile/me/education/:id`
`DELETE /profile/me/education/:id`

Request: `{ "school": "UI", "degree": "Computer Science", "year": "2022" }`

---

## 6\. View Other User's Profile

`GET /profile/:user_id`

Same structure as my profile but read-only. Certain fields hidden based on privacy settings.

Response 200: profile object + `actions` field:

```json
{
  "actions": {
    "can_connect": true,
    "can_message": false,
    "can_add_to_team": false,
    "can_report": true,
    "can_block": true
  }
}
```

---

## 7\. Get Startup Profile

`GET /startups/:startup_id`

Response 200:

```json
{
  "startup_id": "stp_456",
  "name": "ConnectX",
  "logo_url": "https://...",
  "stage": "mvp",
  "stage_label": "MVP",
  "founded_by": { "user_id": "usr_abc", "name": "John Carter" },
  "industries": ["saas", "ai_ml"],
  "team_size": 4,
  "members": [{ "user_id": "usr_abc", "name": "John", "role": "Founder", "avatar_url": "..." }],
  "description": "Matching platform for startup builders...",
  "open_roles": [{ "role": "CTO", "description": "Looking for technical leader" }],
  "location": "Jakarta",
  "availability": "full_time",
  "is_owner": true
}
```

---

## 8\. Update Startup

`PUT /startups/:startup_id`

Request (partial): `{ "name": "ConnectX", "description": "Updated...", "stage": "live" }`
Only startup owner can update. Response 403 if not owner.

---

## 9\. Upload Startup Logo

`POST /startups/:startup_id/logo`

multipart/form-data. Max 5MB.
Response 200: `{ "logo_url": "https://..." }`

## Metadata
- URL: [https://linear.app/summondev/issue/CON-54/api-contract-profile-view-edit-startup-profile](https://linear.app/summondev/issue/CON-54/api-contract-profile-view-edit-startup-profile)
- Identifier: CON-54
- Status: Duplicate
- Priority: High
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 6 - Profile & Settings (target: 2026-04-21T17:00:00.000Z)
- Due date: 2026-04-20T17:00:00.000Z
- Created: 2026-04-12T08:58:15.339Z
- Updated: 2026-04-12T09:12:48.527Z