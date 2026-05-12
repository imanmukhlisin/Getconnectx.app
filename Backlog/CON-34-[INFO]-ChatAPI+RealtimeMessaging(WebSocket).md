# [BE] Chat API + real-time messaging (WebSocket)

`Chat:`

* `POST /conversations (create on match)`
* `GET /conversations (list all)`
* `GET /conversations/:id/messages (paginated)` 
* `POST /conversations/:id/messages (send)`

`Real-time:`

* `WebSocket connection per user`
* `Emit on new message, typing indicator`
* `Online/offline status tracking`

`Media:`

* `POST /upload (image upload for chat)`
* `Shared media list per conversation`

## Metadata
- URL: [https://linear.app/summondev/issue/CON-34/be-chat-api-real-time-messaging-websocket](https://linear.app/summondev/issue/CON-34/be-chat-api-real-time-messaging-websocket)
- Identifier: CON-34
- Status: In Review
- Priority: Urgent
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 4 - Connects + Chat (target: 2026-04-16T17:00:00.000Z)
- Due date: 2026-04-15T17:00:00.000Z
- Created: 2026-04-04T09:45:40.795Z
- Updated: 2026-04-15T17:55:08.482Z