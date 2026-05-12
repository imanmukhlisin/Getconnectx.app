# [BE] Connects API + Match expiry + Push notifications (FCM)

Connects:

* GET /connects (list people who swiped connect on you, blurred for free)
* GET /matches (mutual connects)
* Match expiry logic (configurable timer, auto-expire if no conversation)

Push Notifications (FCM):

* Send on: new match, new message, match expiring, team invite
* Store FCM tokens per device
* Notification preferences check before sending

## Metadata
- URL: [https://linear.app/summondev/issue/CON-33/be-connects-api-match-expiry-push-notifications-fcm](https://linear.app/summondev/issue/CON-33/be-connects-api-match-expiry-push-notifications-fcm)
- Identifier: CON-33
- Status: In Review
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 3 - Discovery & Matching (target: 2026-04-14T17:00:00.000Z)
- Due date: 2026-04-14T17:00:00.000Z
- Created: 2026-04-04T09:45:37.540Z
- Updated: 2026-04-14T17:05:23.154Z