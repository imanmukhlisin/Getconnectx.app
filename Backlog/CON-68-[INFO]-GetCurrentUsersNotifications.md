# API contract: Get current user notifications

## Summary

Add the backend endpoint for the current-user notifications inbox used by the mobile Home bell and `/notifications` screen.

## Endpoint

* Method: `GET`
* Path: `/api/v1/me/notifications`
* Auth: `Authorization: Bearer <token>` using the same JWT as the rest of the app

## Response contract

```ts
type NotificationType =
  | 'match'
  | 'message'
  | 'team_invitation'
  | 'system';

type UserNotification = {
  id: string;
  type: NotificationType;
  title: string;
  body: string;
  createdAt: string;
  readAt: string | null;
  actor: {
    id: string;
    name: string;
    avatarUrl: string | null;
  } | null;
  target: {
    kind: 'match' | 'conversation' | 'startup_invitation' | 'system';
    id: string | null;
    deepLink: string | null;
  };
};

type GetNotificationsResponse = {
  success: true;
  message: string;
  data: {
    unreadCount: number;
    notifications: UserNotification[];
  };
};
```

## Local frontend source of truth

* Contract doc: `src/features/notifications/contracts/BACKEND_CONTRACT.md`
* Mock response: `src/features/notifications/contracts/get-notifications.response.json`
* Contract index: `src/features/notifications/contracts/index.json`

## Frontend expectations

* Notifications are returned newest-first by `createdAt`.
* `unreadCount` equals the number of notifications where `readAt` is `null`.
* An empty notifications array is valid and should return `unreadCount: 0`.
* Unknown future fields are okay as long as the documented shape remains intact.

## Acceptance criteria

* Authenticated request with a valid bearer token returns 200 and the documented shape.
* Missing/invalid bearer token returns the app-standard unauthorized response.
* Empty state returns `success: true`, `unreadCount: 0`, and `notifications: []`.
* Sample data covers at least match, message, team invitation, and system notifications.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-68/api-contract-get-current-user-notifications](https://linear.app/summondev/issue/CON-68/api-contract-get-current-user-notifications)
- Identifier: CON-68
- Status: Backlog
- Priority: Medium
- Assignee: Unassigned
- Created: 2026-04-29T03:34:53.119Z
- Updated: 2026-04-29T03:34:53.119Z