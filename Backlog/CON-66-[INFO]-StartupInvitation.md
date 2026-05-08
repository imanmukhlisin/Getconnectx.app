# [BE] API CONTRACT - Startup Invitation

# ConnectX Team Invitations — Backend Contract

Source of truth for the Team invitation inbox. The frontend already sends outgoing invitations from the Team tab, and this contract adds the recipient-side APIs needed to fetch incoming invitations and respond to them.

## 1\. Base URL & auth

```txt
Base URL: /api/v1
Auth:     Bearer token (same JWT used by the rest of the app)
```

All timestamps are ISO-8601 strings in UTC.

## 2\. Endpoint list

| Method | Path | Purpose |
| -- | -- | -- |
| GET | `/me/startup-invitations` | Fetch incoming startup invitations for the authenticated user. |
| POST | `/me/startup-invitations/:invitationId/respond` | Accept or deny a specific invitation. |

## 3\. Core types

```ts
type InvitationDecision = 'accept' | 'deny';
type InvitationStatus = 'pending' | 'accepted' | 'denied' | 'expired';

type TeamEntityOption = {
  id: string;
  label: string;
};

type StartupInvitation = {
  id: string;
  recipientEmail: string;
  status: InvitationStatus;
  sentAt: string;
  expiresAt: string | null;
  startup: {
    id: string;
    name: string;
    description: string;
    industry: TeamEntityOption;
    stage: TeamEntityOption;
  };
  inviter: {
    userId: string;
    name: string;
    email: string;
    avatarUrl: string | null;
    roleLabel: string | null;
  };
};

type FetchStartupInvitationsResponse = {
  success: true;
  data: {
    invitations: StartupInvitation[];
  };
};

type RespondToStartupInvitationRequest = {
  decision: InvitationDecision;
};

type RespondToStartupInvitationResponse = {
  success: true;
  message: string;
  data: {
    invitationId: string;
    status: Extract<InvitationStatus, 'accepted' | 'denied'>;
    startupId: string;
    actedAt: string;
  };
};
```

## 4\. Field meanings

| Field | Meaning |
| -- | -- |
| `id` | Stable invitation identifier used by the respond endpoint. |
| `recipientEmail` | Email address the invite was sent to. |
| `status` | Current invitation state. FE treats only `pending` as actionable. |
| `sentAt` | When the invitation was created. |
| `expiresAt` | Expiration timestamp. `null` means no expiry policy is currently set. |
| `startup` | Summary of the startup the user is being invited to join. |
| `inviter` | Summary of the team member who sent the invitation. |
| `actedAt` | When the invitation was accepted or denied. Present only in respond success payloads. |

## 5\. Example payloads

### 5.1 Fetch invitations

See [get-startup-invitations.response.json](<./get-startup-invitations.response.json>).

### 5.2 Respond request

See [respond-startup-invitation.request.json](<./respond-startup-invitation.request.json>).

### 5.3 Respond success

See:

* [respond-startup-invitation.accept.response.json](<./respond-startup-invitation.accept.response.json>)
* [respond-startup-invitation.deny.response.json](<./respond-startup-invitation.deny.response.json>)

## 6\. State transitions

* `pending -> accepted`
* `pending -> denied`
* `accepted`, `denied`, and `expired` invitations are no longer actionable
* Responding to an already non-pending invitation should return a business error from BE rather than silently mutate it again

## 7\. Frontend expectations

* An empty `invitations` array is a valid success response.
* The Team tab fetches invitations primarily when the user has no active startup yet.
* After a successful `accept`, FE immediately refetches team overview so the screen can transition into the joined startup state.
* After a successful `deny`, FE removes or de-emphasizes the invitation in the inbox.
* If BE adds extra fields later, FE will ignore unknown keys as long as the documented shape remains intact.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-66/be-api-contract-startup-invitation](https://linear.app/summondev/issue/CON-66/be-api-contract-startup-invitation)
- Identifier: CON-66
- Status: Backlog
- Priority: No priority
- Assignee: Unassigned
- Labels: API Contract, Backend
- Created: 2026-04-16T10:04:01.860Z
- Updated: 2026-04-16T10:04:02.335Z