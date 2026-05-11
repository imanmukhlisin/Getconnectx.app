> ✅ **STATUS: IMPLEMENTED**

# [API CONTRACT] - profile response
## Summary

Create the backend API contract for the ConnectX profile response based on the profile UI and align the update contract with the actual Edit Profile modal.

The current authenticated user flow should use the access token from the app and identify the user from the Bearer token on the backend. That means the primary read/write endpoints for the logged-in user should be `/me` endpoints, not `/:id`.

Use `/:id` only when viewing another user's public profile.

The profile "about" text must support two backend-owned variants:

* `startupIdea` when the user owns/represents a startup
* `personalDescription` when the user does not own/represent a startup

Ownership is determined by backend/onboarding state. The frontend should not send ownership or about kind in PATCH requests.

## Auth model

Protected profile endpoints should require:

```http
Authorization: Bearer <access_token>
```

The backend should resolve the current user from the token, not from a user ID sent by the client.

---

## Read my profile endpoint

`GET /api/v1/me/profile`

### Headers

```http
Authorization: Bearer <access_token>
```

## Read my profile response contract

```json
{
  "success": true,
  "message": "Profile fetched successfully",
  "data": {
    "id": "usr_123456",
    "teamId": "team_connectx_001",
    "profileType": "founder",
    "name": "John Carter",
    "headline": "Startup Founder",
    "photoUrl": "https://cdn.connectx.app/profiles/usr_123456/photo.jpg",
    "location": {
      "city": "Jakarta",
      "country": "Indonesia",
      "display": "Jakarta, Indonesia"
    },
    "stats": {
      "connections": 47,
      "teamsJoined": 2,
      "matches": 156
    },
    "badges": [
      { "id": "startup-founder", "label": "Startup Founder" },
      { "id": "top-builder", "label": "Top Builder" },
      { "id": "open-source", "label": "Open Source" }
    ],
    "startup": {
      "name": "SupplyPilot AI",
      "tagline": "AI logistics planning for growing Southeast Asian SMEs.",
      "stage": {
        "value": "mvp",
        "label": "MVP",
        "details": [
          { "id": "q_user_count", "label": "Users", "value": 42 },
          { "id": "q_mau", "label": "Monthly active users", "value": 28 },
          { "id": "q_mvp_revenue", "label": "Revenue", "value": "Pre-revenue pilots" },
          { "id": "q_growth_rate", "label": "Growth rate", "value": "15% MoM pilot usage growth" }
        ]
      },
      "industries": [
        { "id": "logistics", "name": "Logistics" },
        { "id": "supply_chain_tech", "name": "Supply Chain Tech" },
        { "id": "ai", "name": "AI" }
      ],
      "links": [
        { "label": "Website", "url": "https://supplypilot.ai" },
        { "label": "LinkedIn", "url": "https://linkedin.com/company/supplypilot-ai" },
        { "label": "Pitch deck", "url": "https://pitch.com/supplypilot-ai" }
      ]
    },
    "sections": {
      "about": {
        "kind": "startupIdea",
        "title": "Startup Idea",
        "value": "AI-powered supply chain platform that optimizes logistics for SMEs across Southeast Asia."
      },
      "personalityAndHobbies": {
        "title": "Personality & Hobbies",
        "items": [
          { "id": "ph_1", "name": "Goal-Oriented" },
          { "id": "ph_2", "name": "Problem Solver" },
          { "id": "ph_3", "name": "Coffee Enthusiast" },
          { "id": "ph_4", "name": "Avid Reader" },
          { "id": "ph_5", "name": "Marathon Runner" },
          { "id": "ph_6", "name": "Guitar Player" }
        ]
      },
      "skills": {
        "title": "Skills",
        "items": [
          { "id": "sk_1", "name": "Strategy" },
          { "id": "sk_2", "name": "BD" },
          { "id": "sk_3", "name": "Fundraising" },
          { "id": "sk_4", "name": "Product" },
          { "id": "sk_5", "name": "GTM" }
        ]
      },
      "interests": {
        "title": "Interests",
        "items": [
          { "id": "in_1", "name": "Fintech" },
          { "id": "in_2", "name": "AI/ML" },
          { "id": "in_3", "name": "SaaS" },
          { "id": "in_4", "name": "B2B" }
        ]
      },
      "highlights": {
        "items": [
          "5+ years startup experience",
          "MBA, London Business School",
          "English, Bahasa Indonesia"
        ]
      }
    },
    "createdAt": "2026-04-12T10:00:00.000Z",
    "updatedAt": "2026-04-12T10:00:00.000Z"
  }
}
```

### About section variants

Startup owner / startup representative:

```json
{
  "kind": "startupIdea",
  "title": "Startup Idea",
  "value": "AI-powered supply chain platform that optimizes logistics for SMEs across Southeast Asia."
}
```

Non-startup user:

```json
{
  "kind": "personalDescription",
  "title": "Description",
  "value": "Product-minded operator looking to join an early-stage team and help scale GTM systems."
}
```

`sections.about` may be omitted if the profile does not have about text yet. If present, `kind` must be one of `startupIdea` or `personalDescription`.

### Startup data for startup representatives

`data.startup` is optional. Include it only when the user represents or owns a startup, including users who completed onboarding with `q_use_connectx === "startup"`. Omit it for regular builder/non-startup profiles.

Backend source mapping from onboarding answers:

* `startup.name` from `q_startup_name`
* `startup.tagline` from `q_startup_tagline`
* `startup.stage.value` from `q_startup_stage`, one of `idea`, `mvp`, `live`, `scale`
* `startup.stage.label` is the display label for that stage, e.g. `Idea`, `MVP`, `Live`, `Scale`
* `startup.stage.details` contains only the visible traction answers for the selected stage
* `startup.industries` from `q_industries_interest`, using the selected option ids and labels
* `startup.links` from `q_website`, `q_startup_linkedin`, `q_twitter`, `q_instagram`, `q_pitch_deck`

Stage detail examples:

* `idea`: `q_has_prototype`, `q_prototype_link`, `q_waitlist_size`, `q_validation_methods`
* `mvp`: `q_user_count`, `q_mau`, `q_mvp_revenue`, `q_growth_rate`
* `live`: `q_mrr`, `q_live_users`, `q_retention`, `q_key_metrics`
* `scale`: `q_funding_raised`, `q_scale_team_size`, `q_arr`, `q_investors`

Link normalization:

* Omit empty/null links.
* Return each link as `{ "label": string, "url": string }`.
* Normalize social handles to URLs where possible, e.g. `@connectx` from `q_twitter` becomes `https://x.com/connectx`.
* Suggested labels: `Website`, `LinkedIn`, `Twitter / X`, `Instagram`, `Pitch deck`.

---

## Read public profile endpoint

`GET /api/v1/profiles/:id`

Use this only for viewing another user's profile, discovery pages, or profile detail screens for other users.

This endpoint can be:

* public, if ConnectX profiles are public inside the app
* or protected, if only authenticated users can browse profiles

It should not be used by the current user to fetch their own editable profile screen.

---

## Actual edit modal fields

Based on the current Edit Profile UI, the editable fields are only:

* `name`
* `headline`
* `location` (single display string in the form UI)
* `about` (startup idea or personal description, depending on backend ownership state)
* `personalityAndHobbies` (multi-select)

This means the PATCH contract should only accept fields exposed by the current modal. Startup metadata is backend-owned from onboarding/startup profile state and is not edited by the current Edit Profile modal.

## Update my profile endpoint

`PATCH /api/v1/me/profile`

### Headers

```http
Authorization: Bearer <access_token>
```

## PATCH request contract

Use IDs instead of slugs or names for multi-select fields. This is more stable for the frontend and backend, avoids breakage when labels change, and maps cleanly to lookup tables.

The frontend sends only the about text as `about`. Backend determines whether it updates the startup idea or personal description from the current user's onboarding/profile ownership state.

```json
{
  "name": "John Carter",
  "headline": "Startup Founder",
  "location": "Jakarta, Indonesia",
  "about": "AI-powered supply chain platform that optimizes logistics for SMEs across Southeast Asia.",
  "personalityAndHobbyIds": ["ph_1", "ph_2", "ph_3", "ph_4", "ph_5", "ph_6"]
}
```

## PATCH response contract

```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": "usr_123456",
    "name": "John Carter",
    "headline": "Startup Founder",
    "photoUrl": "https://cdn.connectx.app/profiles/usr_123456/photo.jpg",
    "location": {
      "city": "Jakarta",
      "country": "Indonesia",
      "display": "Jakarta, Indonesia"
    },
    "sections": {
      "about": {
        "kind": "startupIdea",
        "title": "Startup Idea",
        "value": "AI-powered supply chain platform that optimizes logistics for SMEs across Southeast Asia."
      },
      "personalityAndHobbies": {
        "title": "Personality & Hobbies",
        "items": [
          { "id": "ph_1", "name": "Goal-Oriented" },
          { "id": "ph_2", "name": "Problem Solver" }
        ]
      }
    },
    "updatedAt": "2026-04-12T10:00:00.000Z"
  }
}
```

## Supporting lookup endpoint

To render the edit modal safely, expose available selectable options from the backend:

`GET /api/v1/profile-options`

```json
{
  "success": true,
  "data": {
    "personalityAndHobbies": [
      { "id": "ph_1", "name": "Goal-Oriented" },
      { "id": "ph_2", "name": "Problem Solver" },
      { "id": "ph_3", "name": "Coffee Enthusiast" },
      { "id": "ph_4", "name": "Avid Reader" },
      { "id": "ph_5", "name": "Marathon Runner" },
      { "id": "ph_6", "name": "Guitar Player" }
    ]
  }
}
```

## Simple DB direction

Keep text fields in `user_profiles`, and use lookup + join tables for selectable tags.

The backend can source `about` and `startup` from different profile columns/tables depending on ownership:

* startup owner/representative: startup idea / startup description source plus startup profile metadata from onboarding/startup tables
* non-startup user: personal profile description source

```sql
users
- id
- email

user_profiles
- id
- user_id
- name
- headline
- photo_url
- location_display
- personal_description

startup_profiles
- id
- owner_user_id
- name
- tagline
- stage
- startup_idea

startup_profile_industries
- startup_profile_id
- industry_id

startup_profile_links
- startup_profile_id
- label
- url

personality_hobbies
- id
- name
- slug

user_personality_hobbies
- user_id
- personality_hobby_id
```

## Validation notes

* `name`: string, required, max 100
* `headline`: string, required, max 120
* `location`: string, required, max 120
* `about`: string, required, max 500
* `personalityAndHobbyIds`: string\[\], max 6 selected
* every `personalityAndHobbyIds[]` must exist in lookup table
* response `sections.about.kind`: `startupIdea` or `personalDescription`
* `startup` is optional and omitted for non-startup profiles
* `startup.stage.value`: one of `idea`, `mvp`, `live`, `scale`
* `startup.stage.details[].value`: string, number, string array, or null
* `startup.links[]`: omit empty onboarding links; each returned item must include `label` and normalized `url`

## Suggested frontend note

The edit modal should send only fields the user can edit right now. Skills, interests, badges, highlights, about kind, and startup metadata should not be part of this PATCH until they are exposed in the UI.

In Expo/React Native, the app should attach the access token in the Authorization header. The backend should read the current user from that token and return the matching profile.

## Suggested TypeScript interfaces

```ts
export type ProfileAboutKind = "startupIdea" | "personalDescription";

export interface ProfileAboutSection {
  kind: ProfileAboutKind;
  title: string;
  value: string;
}

export type ProfileStartupStageValue = "idea" | "mvp" | "live" | "scale";

export type ProfileStartupStageDetailValue = string | number | string[] | null;

export interface ProfileStartupStageDetail {
  id: string;
  label: string;
  value: ProfileStartupStageDetailValue;
}

export interface ProfileStartupData {
  name: string;
  tagline: string;
  stage: {
    value: ProfileStartupStageValue;
    label: string;
    details: ProfileStartupStageDetail[];
  };
  industries: Array<{
    id: string;
    name: string;
  }>;
  links: Array<{
    label: string;
    url: string;
  }>;
}

export interface MyProfileResponse {
  success: boolean;
  message: string;
  data: {
    id: string;
    teamId: string;
    profileType: "founder" | "builder" | "investor" | "operator" | "student";
    name: string;
    headline: string;
    photoUrl: string | null;
    location: {
      city: string;
      country: string;
      display: string;
    };
    stats: {
      connections: number;
      teamsJoined: number;
      matches: number;
    };
    badges: Array<{
      id: string;
      label: string;
    }>;
    startup?: ProfileStartupData;
    sections: {
      about?: ProfileAboutSection;
      personalityAndHobbies?: {
        title: string;
        items: Array<{ id: string; name: string }>;
      };
      skills?: {
        title: string;
        items: Array<{ id: string; name: string }>;
      };
      interests?: {
        title: string;
        items: Array<{ id: string; name: string }>;
      };
      highlights?: {
        items: string[];
      };
    };
    createdAt: string;
    updatedAt: string;
  };
}

export interface UpdateMyProfileRequest {
  name: string;
  headline: string;
  location: string;
  about: string;
  personalityAndHobbyIds: string[];
}
```

## Frontend implementation status

Implemented in the Expo app:

* `sections.startupIdea` replaced with typed `sections.about`
* edit modal PATCH payload now sends `about`
* UI labels switch between Startup Idea and Description from `about.kind`
* mock profile data now includes `data.startup`
* profile screen renders a startup card from mock data while API fetching is temporarily disabled for this screen
* `npm run lint` passes with two unrelated pre-existing warnings in `src/features/matches/components/match-analysis-screen.tsx` and `src/features/onboarding_test/components/onboarding-screen.tsx`

---

## Backend Implementation Status
**Status: ✅ Done**

| Sub-feature | Status | Catatan |
|---|---|---|
| `GET /api/v1/me/profile` | ✅ Done | Bearer token auth, resolve user dari token |
| `PATCH /api/v1/me/profile` | ✅ Done | Update profile data |
| `GET /api/v1/profiles/{id}` | ✅ Done | Lihat profil user lain (public) |
| `GET /api/v1/profile-options` | ✅ Done | Daftar opsi untuk edit profile |
| `PUT /api/v1/profile/fcm-token` | ✅ Done | Register/update FCM token dari device |
| `startupIdea` vs `personalDescription` | ✅ Done | Backend menentukan variant berdasarkan onboarding state |
| LinkedIn scraping → profile sync | ✅ Done | Apify HarvestAPI scraper, webhook callback, semua field dimapping termasuk `avatar_url`, `position`, `location`, `experience`, `education` |

**Catatan penting untuk FE:**
- `profileId` di response adalah UUID user, bukan prefixed string
- Field `credential` di response memuat data LinkedIn (photo, headline, experience, education)
