# [BE] Team API + Application tracking + Team invites

Team:

* POST /teams/add (add member: role, equity, commitment)
* GET /teams (roster with members, roles, equity, status)
* PUT /teams/:memberId (edit role/equity)
* DELETE /teams/:memberId (remove)

Applications (Co-Founder view):

* GET /applications (list with status: applied/in review/interviews)
* Application status counts

Invites:

* POST /invites/send
* POST /invites/:id/accept
* POST /invites/:id/decline
* POST /invites/:id/revoke
* GET /invites (pending sent + received)

## Metadata
- URL: [https://linear.app/summondev/issue/CON-35/be-team-api-application-tracking-team-invites](https://linear.app/summondev/issue/CON-35/be-team-api-application-tracking-team-invites)
- Identifier: CON-35
- Status: Backlog
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 4 - Connects + Chat (target: 2026-04-16T17:00:00.000Z)
- Due date: 2026-04-16T17:00:00.000Z
- Created: 2026-04-04T09:45:45.692Z
- Updated: 2026-04-16T17:55:01.489Z