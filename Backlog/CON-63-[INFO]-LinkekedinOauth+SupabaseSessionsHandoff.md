# LinkedIn OAuth + Supabase Session Handoff

# 

## Summary

This document defines the recommended LinkedIn login architecture for ConnectX.

The goal is to:

* keep LinkedIn OAuth working for the native mobile app
* keep the LinkedIn client secret on the backend only
* end with a real Supabase session in the app
* avoid depending on `provider_token` from the frontend after login

Linked issue: [CON-49](https://linear.app/summondev/issue/CON-49/api-contract-auth-register-login-otp-social-auth)

## Final Architecture

Use a backend HTTPS callback for LinkedIn, then create a Supabase magic-link session for the same user.

### High-level flow

 1. The app opens a backend URL such as `https://getconnectxapp.vercel.app/auth/linkedin/start`.
 2. The backend redirects the browser to LinkedIn's authorization URL.
 3. LinkedIn redirects to the backend callback URL, for example `https://getconnectxapp.vercel.app/auth/linkedin/callback`.
 4. The backend exchanges the LinkedIn `code` for a LinkedIn access token using the LinkedIn client secret.
 5. The backend fetches the LinkedIn user profile.
 6. The backend finds or creates the matching Supabase user.
 7. The backend generates a Supabase magic link with `redirectTo=connectx://oauth/linkedin-callback`.
 8. The backend redirects to the magic link, or returns it to the app for opening.
 9. Supabase redirects back to `connectx://oauth/linkedin-callback#access_token=...&refresh_token=...`.
10. The app parses the fragment tokens and calls `supabase.auth.setSession(...)`.
11. The existing auth listener and hydration in the app finishes login.

## Why this architecture

This approach is preferred because:

* LinkedIn requires registered redirect URLs to be `http` or `https`, not a custom app scheme.
* The LinkedIn client secret stays on the backend.
* The backend owns LinkedIn token exchange and profile retrieval.
* The frontend only needs to finish the final Supabase session.
* We do not need the frontend to send `provider_token` back to the backend after login.

## Important Clarification

The frontend should not be responsible for sending a LinkedIn `provider_token` to the backend after login in this architecture.

Reason:

* the backend already receives the LinkedIn authorization `code`
* the backend already exchanges that code for the LinkedIn access token
* the backend can fetch LinkedIn user info directly
* the backend can then mint the final Supabase login path via magic link

So the clean responsibility split is:

* backend owns LinkedIn OAuth token exchange
* frontend owns the final Supabase session setup

## Redirect URIs

There are two different redirect URIs in this design.

### 1\. LinkedIn redirect URI

This is the URI registered in the LinkedIn Developer Portal.

Recommended value:

`https://getconnectxapp.vercel.app/auth/linkedin/callback`

Requirements:

* must be `https://...`
* must exactly match the value used by the backend in the LinkedIn authorize request and token exchange request
* cannot be `connectx://...`

### 2\. Supabase redirect URI

This is the URI used in the Supabase magic-link flow.

Recommended value:

`connectx://oauth/linkedin-callback`

Requirements:

* must be added to the Supabase Auth redirect allow list
* must match the app scheme in `app.json`

## Frontend Responsibilities

The frontend should do the following.

### 1\. Start login

* User taps Continue with LinkedIn.
* The app opens the backend start URL in the browser.
* Example: `https://getconnectxapp.vercel.app/auth/linkedin/start`

### 2\. Handle final callback

The app should handle `connectx://oauth/linkedin-callback` for the final Supabase redirect.

It should support:

* warm app return from browser
* cold start from deep link

### 3\. Parse Supabase tokens

When Supabase redirects back, the access token and refresh token are expected in the URL fragment.

Example:

`connectx://oauth/linkedin-callback#access_token=...&refresh_token=...`

The app must parse:

* `access_token`
* `refresh_token`

Then call:

```ts
await supabase.auth.setSession({
  access_token,
  refresh_token,
})
```

### 4\. Preserve LinkedIn login method

Because the final Supabase session is created through a magic link, the frontend may need a temporary marker such as `pendingLinkedInAuth=true` so the app can preserve `method: 'linkedin'` during auth hydration instead of inferring the wrong method.

### 5\. Keep existing Google flow unchanged

The Google native SDK flow should remain separate.

Google can continue using:

* native Google SDK in app
* backend verify endpoint with `provider_token` and `fcm_token`

LinkedIn should not copy that pattern unless the backend explicitly needs an extra post-login registration step.

### 6\. Optional backend follow-up after Supabase login

If the backend still needs the app's `fcm_token`, the app can call a backend endpoint after Supabase login is complete.

That request should contain:

* authenticated app or Supabase user context
* `fcm_token`

It should not need LinkedIn `provider_token`.

## Backend Responsibilities

The backend should do the following.

### 1\. Start route

Create a route such as:

`GET /auth/linkedin/start`

Responsibilities:

* generate a CSRF `state`
* store state in cookie, session, or another verifiable backend mechanism
* redirect to LinkedIn authorize URL with:
  * `response_type=code`
  * `client_id`
  * `redirect_uri`
  * `scope=openid profile email`
  * `state`

### 2\. Callback route

Create a route such as:

`GET /auth/linkedin/callback`

Responsibilities:

* validate `state`
* read `code`
* exchange `code` with LinkedIn token endpoint using:
  * `client_id`
  * `client_secret`
  * `redirect_uri`
  * `grant_type=authorization_code`
* fetch LinkedIn profile using the returned access token

Expected user data target:

* LinkedIn subject ID
* email
* display name
* picture if available

### 3\. Supabase user management

Using the Supabase service role key:

* find existing user by email
* create the user if it does not exist
* optionally update profile metadata as needed

### 4\. Supabase magic link

Generate a magic link for the resolved user with:

* type: `magiclink`
* email: user's email
* `redirectTo: connectx://oauth/linkedin-callback`

### 5\. Final redirect

Preferred backend behavior:

* redirect the browser directly to the generated Supabase `action_link`

Alternative behavior:

* return `{ magicLink }` to the app and let the app open it

Preferred option is the direct redirect because it simplifies the frontend and removes one extra network hop.

## Suggested Backend Contract

### Preferred routes

* `GET /auth/linkedin/start`
* `GET /auth/linkedin/callback`

### Optional follow-up route

If the app must send FCM after login:

* `POST /auth/push-token`

Example payload:

```json
{
  "fcm_token": "..."
}
```

This route should be authenticated using the user's final app or Supabase session, not LinkedIn tokens.

## Supabase Setup

### Supabase Auth redirect allow list

Add:

`connectx://oauth/linkedin-callback`

### Supabase admin usage

Backend must use the service role key for:

* user lookup and creation
* magic-link generation

This key must never be exposed to the app.

## LinkedIn Setup

In LinkedIn Developer Portal:

* enable the LinkedIn auth product required by the integration
* register the exact HTTPS redirect URI:

  `https://getconnectxapp.vercel.app/auth/linkedin/callback`

Important:

* LinkedIn redirect URLs must be `http` or `https`
* custom app schemes like `connectx://...` are not valid as LinkedIn redirect URLs

## Frontend Edge Cases

The app should handle these cases gracefully:

* user cancels LinkedIn login
* LinkedIn callback arrives without `code`
* backend callback fails or returns error
* magic-link redirect arrives without `access_token` or `refresh_token`
* app cold-starts from the Supabase callback URL
* stale pending LinkedIn auth marker exists and must be cleared

## Recommended Logging

### Frontend logs

* LinkedIn login start
* callback received
* Supabase fragment token presence
* `setSession()` success or failure

### Backend logs

* start route hit
* callback route hit
* code exchange success or failure
* LinkedIn user profile fetch success or failure
* Supabase user lookup or creation success or failure
* magic-link generation success or failure

## Final Recommendation

For ConnectX, treat LinkedIn as:

* backend-owned OAuth
* frontend-completed Supabase session

Do not rely on `session.provider_token` from Supabase for LinkedIn.

Do not require the app to send LinkedIn `provider_token` back to the backend after login.

Keep the app responsible only for final session completion and post-login device setup such as FCM token registration.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-63/linkedin-oauth-supabase-session-handoff](https://linear.app/summondev/issue/CON-63/linkedin-oauth-supabase-session-handoff)
- Identifier: CON-63
- Status: Backlog
- Priority: No priority
- Assignee: Unassigned
- Related issues: CON-49
- Created: 2026-04-13T07:18:03.529Z
- Updated: 2026-04-13T07:18:03.529Z