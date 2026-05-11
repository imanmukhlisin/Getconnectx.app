> ✅ **STATUS: IMPLEMENTED**

# [API Contract] Connects + Match Expiry
**Contract Extension: Connect Matches + Team Fit Analysis**

**Summary**
Extend the Connect API contract so the Connect tab can:

This keeps GET /api/v1/matches as the list endpoint and adds a new detail endpoint:
GET /api/v1/matches/:matchId/analysis

Protected endpoints use:

`Authorization: Bearer <access token>`

The backend resolves the current user from the token.`

---

`1. Connect`**matches list**

Endpoint
`GET /api/v1/matches`

`Query params`

* `page:` number

**Response**

```json
{
  "success": true,
  "message": "Matches fetched successfully",
  "data": {
    "likesYou": {
      "locked" : true,
      "items": [
        {
          "likeId": "like_201",
          "likedAt": "2026-04-13T03:20:00Z",
          "user": {
            "userId": "usr_like_201",
            "name": "Sinta Prameswari",
            "photoUrl": "https://cdn.connectx.app/profiles/sinta.jpg",
            "headline": "Brand Strategist",
            "location": "Jakarta, Indonesia"
          }
        }
      ],
      "totalNew": 12
    },
    "items": [
      {
        "matchId": "mtc_789",
        "status": "active",
        "matchedAt": "2026-04-10T08:00:00Z",
        "expiresAt": "2026-04-17T08:00:00Z",
        "expiresInDays": 6,
        "hasMessaged": false,
        "isOnline": true,
        "conversationId": "conv_abc",
        "user": {
          "userId": "usr_123",
          "name": "Ardi Wijaya",
          "photoUrl": "https://cdn.connectx.app/profiles/ardi.jpg",
          "headline": "Full-Stack Engineer",
          "location": "Jakarta, Indonesia"
        },
        "fitSummary": {
          "score": 87,
          "label": "Strong Founding Team Fit",
          "insight": "Business + technical founder pairing with strong startup alignment."
        },
        "actions": {
          "canChat": true,
          "canViewAnalysis": true
        }
      }
    ],
    "total": 5,
    "page": 1,
    "limit": 10,
    "hasMore": false
  }
}
```

**Notes**

* conversationId is used by the chat icon to open the conversation screen.
* fitSummary.score drives the compatibility percentage shown on the list row.
* actions.canChat and actions.ca`nViewAnalysis `let frontend disable/hide unavailable actions cleanly.
* This` endpoint should` return mutual matches only.

---

2\. Match analysis detail

Endpoint
`GET /api/v1/matches/:matchId/analysis`

Purpose
Provides a fully render-ready payload for the “Startup Team Fit Analysis” stack screen.

Response

```json
{
  "success": true,
  "message": "Match analysis fetched successfully",
  "data": {
    "matchId": "mtc_789",
    "conversationId": "conv_abc",
    "status": "active",
    "generatedAt": "2026-04-13T10:00:00.000Z",
    "user": {
      "userId": "usr_123",
      "name": "Ardi Wijaya",
      "photoUrl": "https://cdn.connectx.app/profiles/ardi.jpg",
      "headline": "Full-Stack Engineer",
      "location": "Jakarta, Indonesia"
    },
    "analysis": {
      "compatibilityScore": 87,
      "label": "Strong Founding Team Fit",
      "subtitle": "You & Ardi Wijaya",
      "skillComplementarity": {
        "title": "Skill Complementarity",
        "youBring": ["Strategy", "Business Development", "Fundraising"],
        "theyBring": ["React", "Node.js", "PostgreSQL"],
        "summary": "Your skills complement each other well for building a technology startup."
      },
      "startupVisionAlignment": {
        "title": "Startup Vision Alignment",
        "sharedInterests": ["FinTech", "AI/ML", "SaaS"]
      },
      "commitmentCompatibility": {
        "title": "Commitment Compatibility",
        "you": "Full-time",
        "them": "Full-time"
      },
      "workStyle": {
        "title": "Work Style",
        "traits": ["Remote", "Hybrid", "Fast builder", "Experimental"]
      },
      "potentialRisks": {
        "title": "Potential Risks",
        "items": [
          "Different time zones",
          "Different startup experience levels"
        ]
      },
      "suggestedRoles": {
        "title": "Suggested Roles",
        "you": "CEO",
        "them": "CTO"
      },
      "suggestedTeamStructure": {
        "title": "Suggested Team Structure",
        "roles": ["CEO", "CTO", "Product Designer", "Growth Marketer"]
      }
    }
  }
}
```

Notes

* Response is intentionally UI-ready so the frontend does not need extra profile/scoring calls.
* skillComplementarity must use actual skills data.
* Optional sections may be **omitted**if not available, and frontend will hide those cards safely.

---

3\. Error responses

For `GET /api/v1/matches/:matchId/analysis:`

* 404: match not found or not accessible by current user
* 410: match expired and analysis is no longer available
* 403: reserved only if analysis access is gated separately later

---

**4. Frontend behavior this contract supports**

* Connect tab `use`s GET /api/v1/matches
* chat icon routes to /conversat`ion`/\[conversationId\]
* View button routes to a new stack screen backed by GET /api/v1/matches/:matchId/analysis
* fallback mock responses can mirror these exact shapes if API fails

## Metadata
- URL: [https://linear.app/summondev/issue/CON-51/api-contract-connects-match-expiry](https://linear.app/summondev/issue/CON-51/api-contract-connects-match-expiry)
- Identifier: CON-51
- Status: Backlog
- Priority: High
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 4 - Connects + Chat (target: 2026-04-16T17:00:00.000Z)
- Due date: 2026-04-15T17:00:00.000Z
- Created: 2026-04-12T08:57:20.609Z
- Updated: 2026-05-07T21:19:57.054Z