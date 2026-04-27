Work on Linear issue CON-59:

<issue identifier="CON-59">
<title>[API CONTRACT] - profile response</title>
<description>
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
      {
        "id": "startup-founder",
        "label": "Startup Founder"
      },
      {
        "id": "top-builder",
        "label": "Top Builder"
      },
      {
        "id": "open-source",
        "label": "Open Source"
      }
    ],
    "sections": {
      "about": {
        "kind": "startupIdea",
        "title": "Startup Idea",
        "value": "AI-powered supply chain platform that optimizes logistics for SMEs across Southeast Asia."
      },
      "personalityAndHobbies": {
        "title": "Personality & Hobbies",
        "items": [
          {
            "id": "ph_1",
            "name": "Goal-Oriented"
          },
          {
            "id": "ph_2",
            "name": "Problem Solver"
          },
          {
            "id": "ph_3",
            "name": "Coffee Enthusiast"
          },
          {
            "id": "ph_4",
            "name": "Avid Reader"
          },
          {
            "id": "ph_5",
            "name": "Marathon Runner"
          },
          {
            "id": "ph_6",
            "name": "Guitar Player"
          }
        ]
      },
      "skills": {
        "title": "Skills",
        "items": [
          {
            "id": "sk_1",
            "name": "Strategy"
          },
          {
            "id": "sk_2",
            "name": "BD"
          },
          {
            "id": "sk_3",
            "name": "Fundraising"
          },
          {
            "id": "sk_4",
            "name": "Product"
          },
          {
            "id": "sk_5",
            "name": "GTM"
          }
        ]
      },
      "interests": {
        "title": "Interests",
        "items": [
          {
            "id": "in_1",
            "name": "Fintech"
          },
          {
            "id": "in_2",
            "name": "AI/ML"
          },
          {
            "id": "in_3",
            "name": "SaaS"
          },
          {
            "id": "in_4",
            "name": "B2B"
          }
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

This means the PATCH contract should only accept fields exposed by the current modal.

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
  "personalityAndHobbyIds": [
    "ph_1",
    "ph_2",
    "ph_3",
    "ph_4",
    "ph_5",
    "ph_6"
  ]
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
          {
            "id": "ph_1",
            "name": "Goal-Oriented"
          },
          {
            "id": "ph_2",
            "name": "Problem Solver"
          }
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

The backend can source `about` from different profile columns/tables depending on ownership:

* startup owner/representative: startup idea / startup description source
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
- startup_idea

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

## Suggested frontend note

The edit modal should send only fields the user can edit right now. Skills, interests, badges, highlights, and about kind should not be part of this PATCH until they are exposed in the UI.

In Expo/React Native, the app should attach the access token in the Authorization header. The backend should read the current user from that token and return the matching profile.

## Suggested TypeScript interfaces

```ts
export type ProfileAboutKind = "startupIdea" | "personalDescription";

export interface ProfileAboutSection {
  kind: ProfileAboutKind;
  title: string;
  value: string;
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
    sections: {
      about?: ProfileAboutSection;
      personalityAndHobbies?: {
        title: string;
        items: Array<{
          id: string;
          name: string;
        }>;
      };
      skills?: {
        title: string;
        items: Array<{
          id: string;
          name: string;
        }>;
      };
      interests?: {
        title: string;
        items: Array<{
          id: string;
          name: string;
        }>;
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
* `npx tsc --noEmit` passes
* `npm run lint` passes with one unrelated pre-existing warning in `src/features/home/components/discovery-deck.tsx`
</description>
<team name="ConnectX"/>
<label>Frontend</label>
<label>API Contract</label>
<label>Backend</label>
</issue>