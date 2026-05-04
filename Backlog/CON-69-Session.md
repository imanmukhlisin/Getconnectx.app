# API Contract: GET /api/v1/auth/session

## API Contract: GET /api/v1/auth/session

### Purpose

Return the authenticated user's normalized app session after any login method:

* Email/password after login OTP verification
* Google after backend token verification
* LinkedIn after callback token is available

This lets FE avoid local onboarding-answer inference and initialize Home discovery + premium state consistently.

### Endpoint

GET /api/v1/auth/session

Auth:

Bearer token, same auth token used by the app.

### 200 Response

{

"status": "success",

"message": "Session loaded.",

"data": {

```
"user": {
  "id": "usr_01HSXYZABC123",
  "entity_type": null,
  "email": "dio@example.com",
  "email_verified_at": "2026-05-02T09:15:46.515Z",
  "whatsapp_number": "+628123456789",
  "whatsapp_verified_at": "2026-05-02T09:15:46.515Z",
  "registration_step": 5,
  "is_active": true,
  "is_onboarded": true
},
"discovery_preferences": {
  "default_discovery_mode": "finding_cofounder"
},
"premium": {
  "boost": 3,
  "spotlight": 1,
  "isPremium": true
}
}

}
```

### Field Rules

default_discovery_mode:

* "finding_cofounder" | "building_team" | "explore_startups" | "joining_startups" | null
* Backend owns mapping from onboarding answers to this normalized value.

premium:

* boost: integer, remaining boost credits
* spotlight: integer, remaining spotlight credits
* isPremium: boolean, current premium entitlement status

### Errors

401 if token is missing/invalid.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-69/api-contract-get-apiv1authsession](https://linear.app/summondev/issue/CON-69/api-contract-get-apiv1authsession)
- Identifier: CON-69
- Status: Backlog
- Priority: No priority
- Assignee: Unassigned
- Created: 2026-05-02T09:39:25.929Z
- Updated: 2026-05-02T09:39:26.569Z