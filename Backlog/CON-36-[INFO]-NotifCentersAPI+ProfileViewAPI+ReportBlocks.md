# [BE] Notification center API + Profile view API + Report/Block

Notifications:

* GET /notifications (grouped by date, paginated)
* POST /notifications/mark-read (single or all)
* Notification types: match, message, team, expiry, system

Profile View:

* GET /profile/:userId (other user's profile)
* GET /startups/:id (startup profile with team members)

Report/Block:

* POST /report (reason, description, reported user)
* POST /block/:userId
* DELETE /block/:userId (unblock)
* GET /blocked (list blocked users)

## Metadata
- URL: [https://linear.app/summondev/issue/CON-36/be-notification-center-api-profile-view-api-reportblock](https://linear.app/summondev/issue/CON-36/be-notification-center-api-profile-view-api-reportblock)
- Identifier: CON-36
- Status: Backlog
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 5 - Core Loop Complete (target: 2026-04-17T17:00:00.000Z)
- Due date: 2026-04-17T17:00:00.000Z
- Created: 2026-04-04T09:45:50.063Z
- Updated: 2026-04-17T17:35:04.557Z