# [API Contract ] - Create Team screen API contract for startup overview, required roles, and invitations

## Team Screen + Invitation API Contract

Frontend uses bearer auth only. Backend resolves the authenticated user's active startup/person context.

## Required Endpoints

1. `GET /api/v1/me/startup/team-overview`
2. `GET /api/v1/me/startup/invitation-options`
3. `POST /api/v1/me/startup/invitations`
4. `POST /api/v1/me/startup-invitations/:invitationId/respond`

Additional actions:

5. `DELETE /api/v1/me/startup/invitations/:invitationId`
6. `PATCH /api/v1/startups/:startupId/team-members/:memberId`
7. `DELETE /api/v1/startups/:startupId/team-members/:memberId`

## 1\. GET /api/v1/me/startup/team-overview

Single read model for the Team tab.

Backend should return:

* startup owner/member dashboard with startup summary, roster, required roles, missing roles, team completeness, sent invites, and roster actions
* person/co-founder dashboard with applications, received invites, and discovery actions
* `success: true` with `viewerContext.kind = "person"` for normal users without active startup

Do not use `NO_ACTIVE_STARTUP` for normal person/co-founder dashboard users.

Action visibility is backend-driven:

* `teamRoster.members[].availableActions`
* `teamRoster.actions`
* `myApplications.actions`
* `teamInvites.items[].availableActions`

### Person/co-founder response

```json
{
  "success": true,
  "data": {
    "viewerContext": {
      "kind": "person",
      "hasActiveStartup": false,
      "startupId": null,
      "membershipId": null
    },
    "startup": null,
    "teamRoster": {
      "title": "Your Team",
      "members": [],
      "actions": {
        "inviteViaLink": false,
        "addFromMatches": false
      }
    },
    "myApplications": {
      "title": "My Applications",
      "stats": {
        "applied": 3,
        "inReview": 1,
        "interviews": 1
      },
      "items": [
        {
          "id": "app_123",
          "startupId": "stp_123",
          "startupName": "Atlas Commerce",
          "role": { "id": "technical_cofounder", "label": "Technical Co-Founder" },
          "appliedAt": "2026-04-20T08:30:00.000Z",
          "status": "in_review",
          "statusLabel": "In Review"
        }
      ],
      "actions": {
        "browseStartups": true,
        "discoverMoreStartups": true
      }
    },
    "teamInvites": {
      "title": "Team Invites",
      "items": [
        {
          "id": "inv_123",
          "direction": "received",
          "startupId": "stp_456",
          "startupName": "KlinikOps",
          "role": { "id": "growth_marketer", "label": "Growth Marketer" },
          "email": "person@example.com",
          "sentAt": "2026-04-22T10:00:00.000Z",
          "status": "pending",
          "statusLabel": "Pending",
          "availableActions": ["accept", "decline"]
        },
        {
          "id": "inv_456",
          "direction": "sent",
          "startupId": "stp_789",
          "startupName": "My Startup",
          "role": { "id": "product_designer", "label": "Product Designer" },
          "email": "candidate@example.com",
          "sentAt": "2026-04-23T12:00:00.000Z",
          "status": "pending",
          "statusLabel": "Pending",
          "availableActions": ["revoke"]
        }
      ]
    },
    "teamCompleteness": { "percent": 0, "filledRoles": 0, "targetRoles": 0 },
    "requiredRoles": [],
    "missingRoles": []
  }
}
```

### Startup founder/member response

```json
{
  "success": true,
  "data": {
    "viewerContext": {
      "kind": "startup_owner",
      "hasActiveStartup": true,
      "startupId": "stp_123",
      "membershipId": "tm_1"
    },
    "startup": {
      "id": "stp_123",
      "name": "My Startup",
      "description": "AI-powered supply chain platform for SMEs in Southeast Asia",
      "industry": { "id": "fintech", "label": "Fintech" },
      "stage": { "id": "mvp", "label": "MVP" }
    },
    "teamRoster": {
      "title": "Your Team",
      "members": [
        {
          "id": "tm_1",
          "userId": "usr_1",
          "avatarUrl": null,
          "name": "Founder",
          "role": { "id": "business_founder", "label": "Business" },
          "equityPercent": 40,
          "commitment": "full_time",
          "status": "active",
          "statusLabel": "Active",
          "isCurrentUser": true,
          "availableActions": ["edit_role"]
        },
        {
          "id": "tm_2",
          "userId": "usr_2",
          "avatarUrl": "https://...",
          "name": "Ardi Wijaya",
          "role": { "id": "engineer", "label": "Engineering" },
          "equityPercent": 25,
          "commitment": "full_time",
          "status": "active",
          "statusLabel": "Active",
          "isCurrentUser": false,
          "availableActions": ["edit_role", "remove"]
        }
      ],
      "actions": {
        "inviteViaLink": true,
        "addFromMatches": true
      }
    },
    "myApplications": {
      "title": "My Applications",
      "stats": { "applied": 0, "inReview": 0, "interviews": 0 },
      "items": [],
      "actions": { "browseStartups": false, "discoverMoreStartups": false }
    },
    "teamInvites": {
      "title": "Team Invites",
      "items": []
    },
    "teamCompleteness": { "percent": 50, "filledRoles": 2, "targetRoles": 4 },
    "requiredRoles": [
      { "id": "product_designer", "label": "Product Designer", "status": "open" }
    ],
    "missingRoles": [
      { "id": "product_designer", "label": "Product Designer" }
    ]
  }
}
```

## 2\. GET /api/v1/me/startup/invitation-options

Fetch invite assignment controls before sending an outgoing invitation.

Used by:

* Team tab invite composer
* Chat Add to Team modal
* Chat demo Add to Team modal

Response:

```json
{
  "success": true,
  "data": {
    "roleOptions": [
      { "id": "co_founder", "label": "Co-Founder" },
      { "id": "cto", "label": "CTO" },
      { "id": "engineer", "label": "Engineer" },
      { "id": "product_manager", "label": "Product Manager" },
      { "id": "designer", "label": "Designer" },
      { "id": "marketing", "label": "Marketing" },
      { "id": "operations", "label": "Operations" }
    ],
    "commitmentOptions": [
      { "id": "full_time", "label": "Full-time" },
      { "id": "part_time", "label": "Part-time" },
      { "id": "advisor", "label": "Advisor" }
    ],
    "equity": {
      "min": 1,
      "max": 50,
      "step": 1,
      "defaultValue": 15
    }
  }
}
```

Rules:

* `roleOptions` are backend-owned. FE does not infer them from onboarding catalogs.
* `commitmentOptions[].id` currently supports `full_time | part_time | advisor`.
* `equity` drives the FE slider.

## 3\. POST /api/v1/me/startup/invitations

Send an outgoing team invitation from the resolved active startup context.

Frontend now sends assignment details with the invitation. Backend should persist these values on the pending invitation so sender/recipient views can show the intended role, equity share, and commitment.

Request:

```json
{
  "email": "person@example.com",
  "roleId": "co_founder",
  "equityPercent": 15,
  "commitment": "full_time"
}
```

Request validation:

* `email` is required. FE sends it lowercased.
* `roleId` is required and should match an id from `GET /me/startup/invitation-options.data.roleOptions`.
* `equityPercent` is required and should fit inside `GET /me/startup/invitation-options.data.equity` bounds.
* `commitment` is required and should match an id from `commitmentOptions`; current values are `full_time | part_time | advisor`.

Response:

```json
{
  "success": true,
  "message": "Invitation sent",
  "data": {
    "invitationId": "inv_123",
    "email": "person@example.com",
    "status": "pending"
  }
}
```

## 4\. POST /api/v1/me/startup-invitations/:invitationId/respond

Accept or decline a received invitation.

Request:

```json
{ "decision": "accept" }
```

`decision` remains `accept | deny`. UI copy says Decline, but FE still sends `deny`.

## 5\. DELETE /api/v1/me/startup/invitations/:invitationId

Revoke a sent pending invitation.

Response:

```json
{
  "success": true,
  "message": "Invitation revoked",
  "data": {
    "invitationId": "inv_456",
    "status": "revoked"
  }
}
```

## 6\. PATCH /api/v1/startups/:startupId/team-members/:memberId

Edit role, equity, commitment, or status for an active/pending team member.

```json
{
  "roleId": "engineer",
  "equityPercent": 25,
  "commitment": "full_time",
  "status": "active"
}
```

## 7\. DELETE /api/v1/startups/:startupId/team-members/:memberId

Remove a member from the resolved startup team.

## Acceptance Criteria

* Team screen loads from `GET /api/v1/me/startup/team-overview` for startup and person users.
* Invite assignment modal fetches `GET /api/v1/me/startup/invitation-options` when opened.
* Sending an invitation posts `email`, `roleId`, `equityPercent`, and `commitment`.
* Team tab and chat Add to Team entrypoints use the same assignment fields before sending.
* Received invite actions send accept/deny response requests.
* Sent invitation revoke has a dedicated backend action.
* Member edit/remove actions are driven by backend `availableActions`.

## Out of Scope

* AI inference of roles from startup description
* search ranking or candidate matching logic
* multi-startup switching
* changing startup identity from the Team tab
* complex invitation lifecycle beyond pending/revoked/accepted/denied unless already supported

## Metadata
- URL: [https://linear.app/summondev/issue/CON-61/api-contract-create-team-screen-api-contract-for-startup-overview](https://linear.app/summondev/issue/CON-61/api-contract-create-team-screen-api-contract-for-startup-overview)
- Identifier: CON-61
- Status: Backlog
- Priority: No priority
- Assignee: Unassigned
- Labels: Backend
- Created: 2026-04-12T19:09:49.078Z
- Updated: 2026-04-29T04:54:39.820Z