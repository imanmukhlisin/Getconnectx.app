# [API Contract] Auth - Register, Login, OTP, Social Auth

# Auth API Contract

## Base URL: `/api/v1/auth`

---

## 1\. Register

`POST /auth/register`

Request:

```json
{ "email": "user@example.com", "password": "SecurePass123!", "first_name": "Lolo", "last_name": "Caudan", "locale": "en" }
```

Response 201:

```json
{ "user_id": "usr_abc123", "email": "user@example.com", "access_token": "eyJ...", "refresh_token": "ref_...", "requires_email_verification": true, "requires_wa_verification": true }
```

Errors: 409 `email_already_exists` | 422 `validation_failed`

---

## 2\. Login

`POST /auth/login`

Request: `{ "email": "...", "password": "..." }`

Response 200:

```json
{ "user_id": "usr_abc123", "access_token": "eyJ...", "refresh_token": "ref_...", "email_verified": true, "wa_verified": true, "onboarding_completed": false, "is_pro": false }
```

Errors: 401 `invalid_credentials` | 403 `account_paused`

---

## 3\. Refresh Token

`POST /auth/refresh`

Request: `{ "refresh_token": "ref_..." }`
Response 200: `{ "access_token": "eyJ...", "refresh_token": "ref_new..." }`

---

## 4\. Logout

`POST /auth/logout`
Headers: `Authorization: Bearer <token>`
Response 200: `{ "success": true }`

---

## 5\. Forgot Password

`POST /auth/forgot-password`

Request: `{ "email": "..." }`
Response 200: `{ "message": "Reset link sent" }` (always 200)

---

## 6\. Reset Password

`POST /auth/reset-password`

Request: `{ "token": "reset_token", "new_password": "NewPass456!" }`
Errors: 400 `token_expired`

---

## 7\. Send Email OTP

`POST /auth/otp/email/send`

Response 200: `{ "sent_to": "u***@example.com", "expires_in": 300, "retry_after": 60 }`

---

## 8\. Verify Email OTP

`POST /auth/otp/email/verify`

Request: `{ "code": "123456" }`
Response 200: `{ "email_verified": true }`
Errors: 400 `invalid_code` | 429 `too_many_attempts`

---

## 9\. Send WhatsApp OTP

`POST /auth/otp/wa/send`

Request: `{ "phone": "+6281234567890" }`
Response 200: `{ "sent_to": "+62812****7890", "expires_in": 300, "retry_after": 60 }`

---

## 10\. Verify WhatsApp OTP

`POST /auth/otp/wa/verify`

Request: `{ "phone": "+6281234567890", "code": "123456" }`
Response 200: `{ "wa_verified": true }`

---

## 11\. Social Auth

`POST /auth/social`

Request: `{ "provider": "google", "id_token": "..." }`
Providers: `google`, `linkedin`, `apple`

Response 200/201:

```json
{ "user_id": "usr_abc", "access_token": "eyJ...", "refresh_token": "ref_...", "is_new_user": true, "onboarding_completed": false }
```

---

## 12\. Get Current User

`GET /auth/me`

Response 200:

```json
{ "user_id": "usr_abc", "email": "...", "first_name": "Lolo", "avatar_url": "...", "email_verified": true, "wa_verified": true, "onboarding_completed": true, "is_pro": false, "locale": "en" }
```

---

## 13\. Store Device Info (FCM + Geo)

`POST /auth/device`

Request: `{ "fcm_token": "fcm_abc", "platform": "ios", "latitude": -6.2088, "longitude": 106.8456, "locale": "en" }`
Response 200: `{ "device_id": "dev_123", "detected_region": "ID" }`

---

## Token Format

* Access token: JWT, 15 min expiry
* Refresh token: opaque, 30 day expiry
* All authenticated endpoints require `Authorization: Bearer <token>`

## Rate Limiting

| Endpoint | Limit |
| -- | -- |
| login | 5/15min per email |
| otp send | 1/60s, 5/hour |
| otp verify | 5 attempts per code |
| forgot-password | 3/hour per email |


## Metadata
- URL: [https://linear.app/summondev/issue/CON-49/api-contract-auth-register-login-otp-social-auth](https://linear.app/summondev/issue/CON-49/api-contract-auth-register-login-otp-social-auth)
- Identifier: CON-49
- Status: Duplicate
- Priority: Urgent
- Assignee: Unassigned
- Labels: API Contract, Backend, Frontend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 1 - Auth Ready (target: 2026-04-08T17:00:00.000Z)
- Related issues: CON-63
- Due date: 2026-04-08T17:00:00.000Z
- Created: 2026-04-12T08:56:10.450Z
- Updated: 2026-04-13T07:18:03.529Z