# [BE] PRO/Premium API + Payment integration + Region pricing

PRO API:

* GET /pro/plans (plans by region, auto-detected from geo)
* POST /pro/upgrade (initiate payment)
* GET /pro/status (current subscription)
* PUT /pro/change-plan
* POST /pro/cancel (with retention offer logic)

Payment:

* Stripe integration (global)
* Midtrans integration (Indonesia)
* Webhook handlers for payment confirmation
* Receipt generation

## Metadata
- URL: [https://linear.app/summondev/issue/CON-38/be-propremium-api-payment-integration-region-pricing](https://linear.app/summondev/issue/CON-38/be-propremium-api-payment-integration-region-pricing)
- Identifier: CON-38
- Status: Backlog
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 7 - Launch Ready (target: 2026-04-24T17:00:00.000Z)
- Due date: 2026-04-22T17:00:00.000Z
- Created: 2026-04-04T09:46:08.872Z
- Updated: 2026-04-22T17:15:01.608Z