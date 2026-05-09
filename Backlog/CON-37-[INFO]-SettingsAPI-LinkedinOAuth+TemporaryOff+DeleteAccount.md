# [BE] Settings API + LinkedIn OAuth + Temporary Off + Delete Account

Settings:

* PUT /settings/preferences (matching preferences)
* PUT /settings/notifications (per-type toggles)
* PUT /settings/privacy (visibility, distance, online status, read receipts)
* PUT /settings/language (EN/ID)

LinkedIn OAuth:

* GET /auth/linkedin/callback
* Import profile data (experience, education, skills)

Temporary Off:

* POST /account/pause (hide from discovery, pause matching, retain data)
* POST /account/resume

Delete Account:

* POST /account/delete (soft delete with grace period)
* Download My Data: GET /account/export

## Metadata
- URL: [https://linear.app/summondev/issue/CON-37/be-settings-api-linkedin-oauth-temporary-off-delete-account](https://linear.app/summondev/issue/CON-37/be-settings-api-linkedin-oauth-temporary-off-delete-account)
- Identifier: CON-37
- Status: Backlog
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 6 - Profile & Settings (target: 2026-04-21T17:00:00.000Z)
- Due date: 2026-04-20T17:00:00.000Z
- Created: 2026-04-04T09:46:03.863Z
- Updated: 2026-04-20T17:20:01.323Z