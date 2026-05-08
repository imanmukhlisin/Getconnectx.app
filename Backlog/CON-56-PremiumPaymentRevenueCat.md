# [API Contract] PRO / Premium + Payment

# PRO / Premium + Payment API Contract

## Base URL: `/api/v1`

---

## 1\. Get Plans

`GET /pro/plans`

Region auto-detected from user's geo location. Can override with query param.

Query: `region` (optional, `ID` or `GLOBAL`)

Response 200:

```json
{
  "detected_region": "ID",
  "currency": "IDR",
  "plans": [
    { "id": "plan_weekly_id", "name": "Weekly", "price": 29000, "price_display": "Rp 29.000", "duration_days": 7, "popular": false },
    { "id": "plan_monthly_id", "name": "Monthly", "price": 99000, "price_display": "Rp 99.000", "duration_days": 30, "popular": true },
    { "id": "plan_quarterly_id", "name": "3 Months", "price": 249000, "price_display": "Rp 249.000", "duration_days": 90, "popular": false, "save_percentage": 16 },
    { "id": "plan_annual_id", "name": "Annual", "price": 799000, "price_display": "Rp 799.000", "duration_days": 365, "popular": false, "save_percentage": 33 },
    { "id": "plan_lifetime_id", "name": "Lifetime", "price": 1499000, "price_display": "Rp 1.499.000", "duration_days": null, "popular": false }
  ],
  "features": [
    "See who liked you",
    "Unlimited swipes",
    "Advanced filters",
    "Priority visibility",
    "Rewind swipes"
  ]
}
```

Global plans use USD with different pricing.

---

## 2\. Get PRO Status

`GET /pro/status`

Response 200 (free):

```json
{ "is_pro": false, "plan": null, "expires_at": null }
```

Response 200 (active):

```json
{
  "is_pro": true,
  "plan": { "id": "plan_monthly_id", "name": "Monthly", "price_display": "Rp 99.000" },
  "started_at": "2026-04-01T00:00:00Z",
  "expires_at": "2026-05-01T00:00:00Z",
  "auto_renew": true,
  "payment_method": { "type": "card", "last4": "4242", "brand": "Visa" }
}
```

---

## 3\. Initiate Upgrade

`POST /pro/upgrade`

Request:

```json
{ "plan_id": "plan_monthly_id", "payment_method": "midtrans" }
```

Payment methods: `midtrans` (ID), `stripe` (global), `apple_iap`, `google_play`

Response 200 (redirect):

```json
{
  "payment_id": "pay_abc",
  "status": "pending",
  "redirect_url": "https://payment.midtrans.com/...",
  "expires_at": "2026-04-12T11:00:00Z"
}
```

For IAP: `{ "payment_id": "pay_abc", "status": "pending", "iap_product_id": "com.connectx.pro.monthly" }`

---

## 4\. Confirm Payment (webhook)

`POST /pro/webhook/midtrans` (BE only, Midtrans callback)
`POST /pro/webhook/stripe` (BE only, Stripe webhook)

These are server-to-server. FE does not call these.

---

## 5\. Verify IAP Receipt

`POST /pro/verify-receipt`

Request: `{ "platform": "ios", "receipt_data": "base64..." }`
Response 200: `{ "is_pro": true, "plan": {...}, "expires_at": "..." }`

---

## 6\. Change Plan

`PUT /pro/plan`

Request: `{ "new_plan_id": "plan_annual_id" }`
Response 200: `{ "plan": {...}, "effective_from": "next billing cycle", "new_expires_at": "..." }`

---

## 7\. Cancel Subscription

`POST /pro/cancel`

Response 200:

```json
{
  "status": "cancelling",
  "active_until": "2026-05-01T00:00:00Z",
  "retention_offer": {
    "discount_percentage": 50,
    "plan": { "id": "plan_monthly_id", "discounted_price_display": "Rp 49.500" },
    "expires_in": 86400
  }
}
```

---

## 8\. Accept Retention Offer

`POST /pro/retention-offer/accept`

Response 200: `{ "is_pro": true, "plan": {...}, "discounted": true }`

---

## PRO Feature Flags

FE checks `is_pro` from `/auth/me` or `/pro/status` to toggle:

* Unlocked connects (no blur)
* Advanced filter options
* Rewind button active
* "Who Liked You" section
* Profile views count
* "V1 PREMIUM" badge

## Metadata
- URL: [https://linear.app/summondev/issue/CON-56/api-contract-pro-premium-payment](https://linear.app/summondev/issue/CON-56/api-contract-pro-premium-payment)
- Identifier: CON-56
- Status: Duplicate
- Priority: High
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 7 - Launch Ready (target: 2026-04-24T17:00:00.000Z)
- Due date: 2026-04-22T17:00:00.000Z
- Created: 2026-04-12T08:59:07.393Z
- Updated: 2026-04-12T09:12:52.228Z