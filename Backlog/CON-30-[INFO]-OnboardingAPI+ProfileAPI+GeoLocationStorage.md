# [BE] Onboarding API + Profile API + Geo location storage

Onboarding:

* POST /onboarding/role (save user type: builder/startup)
* POST /onboarding/builder-type (founder/co-founder/team member)
* POST /onboarding/preferences (industries, skills, co-founder type, availability, location)
* GET /onboarding/status (check completion)

Profile:

* POST /profile (create)
* PUT /profile (update)
* GET /profile/me

Geo:

* Store user coordinates from splash
* Region detection (Indonesia vs Global) for pricing

## Metadata
- URL: [https://linear.app/summondev/issue/CON-30/be-onboarding-api-profile-api-geo-location-storage](https://linear.app/summondev/issue/CON-30/be-onboarding-api-profile-api-geo-location-storage)
- Identifier: CON-30
- Status: In Review
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 1 - Auth Ready (target: 2026-04-08T17:00:00.000Z)
- Due date: 2026-04-08T17:00:00.000Z
- Created: 2026-04-04T09:45:15.418Z
- Updated: 2026-04-14T15:07:32.751Z