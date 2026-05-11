> ✅ **STATUS: IMPLEMENTED**

# Define API contract for swipe discovery card stack
# Discovery Home API Contract (`CON-60`)

References:

* `CON-64` for `super_like`
* `CON-65` for rewind

## Summary

* Discovery filter section layout, order, premium presentation, and UI behavior are frontend-owned.
* Backend owns the protected discovery API contracts:
  * `GET /api/v1/discovery/filter-options?mode=<mode>`
  * `POST /api/v1/discovery/cards`
  * `POST /api/v1/discovery/cards/:targetId/action`
* The swipe feed response is a discriminated union:
  * `entityType: "profile"` for `finding_cofounder` and `building_team`
  * `entityType: "startup"` for `explore_startups` and `joining_startups`
* Premium filters remain visible in the app, but backend must still validate premium-only ids and reject unauthorized requests with `PREMIUM_REQUIRED`.

## Auth Model

Protected discovery endpoints require:

```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

The backend resolves the current user from the token.

## Ownership Model

### Frontend owns

* Filter section structure
* Filter section order
* Local labels/descriptions for static sections
* Local premium/locked presentation
* Visual-only UI behavior such as icons, collapse defaults, helper copy, and search UX
* Fallback founder type options when `data.founderTypes` is missing or empty

### Backend owns

* Accepted request schema
* Canonical ids used in filter payloads
* Dynamic catalog ids/labels returned by filter-options
* Validation of mode-specific filter payloads
* Premium enforcement
* Ranked discovery results
* Match score calculation
* Distance calculation
* Excluding the current user and already-swiped targets

## Premium Validation Rules

* Non-premium users can call discovery cards with no filters or basic/unlocked filters.
* Premium-only filter ids may still be visible in the UI, but backend must reject them when the caller is not entitled.
* Premium users can submit both basic and premium filters.
* `{}` and partial request bodies are valid for all authenticated users.
* Premium users may receive AI-personalized default discovery when the request is empty or partial.

Premium validation error:

```json
{
  "success": false,
  "message": "Premium subscription required to use advanced discovery filters",
  "error": {
    "code": "PREMIUM_REQUIRED"
  }
}
```

## Frontend Filter Configuration Reference

This section documents the canonical ids the frontend will send. Frontend still owns section layout, order, and UI presentation. Dynamic catalog-backed option labels may be hydrated from `GET /api/v1/discovery/filter-options`.

Premium sections may carry local frontend metadata such as:

```json
{
  "access": {
    "requiresEntitlement": "connectx_pro",
    "enabled": false,
    "errorCode": "PREMIUM_REQUIRED"
  }
}
```

### `finding_cofounder`

* `goal`: `goal_finding_cofounder`, `goal_building_team`, `goal_explore_startups`, `goal_joining_startups`
* `skillStrengthIds`: `ss_technical`, `ss_product`, `ss_business`, `ss_sales`, `ss_marketing`, `ss_design`, `ss_operations`, `ss_finance`
* `industryIds`: `ind_ai`, `ind_fintech`, `ind_healthtech`, `ind_edtech`, `ind_web3`, `ind_saas`
* `locationAvailability`: `workArrangementIds`, `remoteReady`, `distanceKm`
* `commitmentIds`: `commitment_full_time`, `commitment_part_time`, `commitment_side_project`
* premium `aiMatchPrecision`: `minimumMatchScore`, `priorityPreferenceIds`, `showAiExplainWhyMatch`
* premium `founderBuilderQuality`: `startupExperienceIds`, `leadershipBackgroundIds`
* premium `cofounderReadiness`: `readinessLevelIds`
* premium `globalCompatibility`: `languageIds`, `educationIds`

### `building_team`

* `goal`: `goal_finding_cofounder`, `goal_building_team`, `goal_explore_startups`, `goal_joining_startups`
* `industryIds`: `ind_ai`, `ind_fintech`, `ind_healthtech`, `ind_edtech`, `ind_web3`, `ind_saas`
* `locationAvailability`: `workArrangementIds`, `remoteReady`, `distanceKm`
* `roleNeededIds`: `role_engineer`, `role_product`, `role_designer`, `role_sales`, `role_marketing`, `role_operations`, `role_finance`, `role_growth`, `role_ai_ml`
* `skillIds`: `skill_react`, `skill_python`, `skill_figma`, `skill_growth`, `skill_seo`, `skill_salesforce`
* `commitmentIds`: `commitment_full_time`, `commitment_part_time`, `commitment_side_project`
* premium `aiTalentPrecision`: `minimumMatchScore`, `priorityPreferenceIds`, `showAiExplainWhyMatch`
* premium `executionQuality`: `trackRecordIds`
* premium `globalCompatibility`: `languageIds`
* premium `hiringReadiness`: `availabilityIds`

### `explore_startups`

* `goal`: `goal_finding_cofounder`, `goal_building_team`, `goal_explore_startups`, `goal_joining_startups`
* `startupStageIds`: `stage_idea`, `stage_mvp`, `stage_pre_seed`, `stage_seed`
* `industryIds`: `ind_ai`, `ind_fintech`, `ind_healthtech`, `ind_edtech`, `ind_web3`, `ind_saas`
* `locationAvailability`: `workArrangementIds`, `remoteReady`, `distanceKm`
* `roleNeededIds`: `role_engineer`, `role_designer`, `role_marketing`, `role_sales`, `role_operations`
* premium `startupQuality`: `founderBackgroundIds`
* premium `startupReadiness`: `progressIds`
* premium `opportunityFit`: `conditionIds`
* premium `aiStartupFit`: `minimumFitScore`, `showAiExplainWhyMatch`

### `joining_startups`

* `goal`: `goal_finding_cofounder`, `goal_building_team`, `goal_explore_startups`, `goal_joining_startups`
* `startupStageIds`: `stage_idea`, `stage_mvp`, `stage_pre_seed`, `stage_seed`
* `industryIds`: `ind_ai`, `ind_fintech`, `ind_healthtech`, `ind_edtech`, `ind_web3`, `ind_saas`
* `founderTypeIds`: hydrated from `data.founderTypes` when present; frontend falls back to `ft_technical_founder`, `ft_business_founder`, `ft_product_founder`, `ft_operator_founder` when `founderTypes` is missing or empty
* `locationAvailability`: `workArrangementIds`, `remoteReady`, `distanceKm`
* premium `founderQuality`: `backgroundIds`
* premium `leadershipStrength`: `leadershipIds`
* premium `startupReadiness`: `progressIds`
* premium `equityAndCommitment`: `termIds`

## Discovery Filter Options Endpoint

### Endpoint

```http
GET /api/v1/discovery/filter-options?mode=<mode>
```

### Purpose

Returns frontend-consumable catalog groups for dynamic filter option lists. The frontend still owns section layout, section order, premium presentation, and UI behavior.

### Response Shape

```json
{
  "success": true,
  "message": "Discovery filter options fetched successfully",
  "data": {
    "mode": "joining_startups",
    "city": {
      "id": "q_city",
      "type": "searchable_dropdown",
      "label": "City",
      "placeholder": "Search a city",
      "required": true,
      "meta": { "searchable": true },
      "options": [
        { "id": "opt_city_jakarta", "label": "Jakarta", "value": "jakarta", "group": "Indonesia" }
      ]
    },
    "industries": [],
    "skills": [],
    "roles": [],
    "languages": [],
    "founderTypes": []
  }
}
```

### Catalog Group Shape

`industries`, `skills`, `roles`, `languages`, and `founderTypes` are arrays of catalog groups:

```json
{
  "id": "grp_founder_type_primary",
  "label": "Founder Type",
  "options": [
    { "id": "ft_technical_founder", "label": "Technical Founder", "group": null, "value": "ft_technical_founder" }
  ]
}
```

Option fields:

* `id`: canonical id submitted in discovery filters unless `value` is explicitly used by the frontend control
* `label`: display label
* `group?`: optional display grouping
* `value?`: optional alternate submitted value

### City Shape

`city` is optional and uses searchable dropdown semantics:

```json
{
  "id": "q_city",
  "type": "searchable_dropdown",
  "label": "City",
  "placeholder": "Search a city",
  "required": true,
  "meta": { "searchable": true },
  "options": [
    { "id": "opt_city_jakarta", "label": "Jakarta", "value": "jakarta", "group": "Indonesia" }
  ]
}
```

### Founder Type Hydration

* `data.founderTypes` is used by `joining_startups.founderTypeIds`.
* If `founderTypes` is missing or all groups have no options, the frontend falls back to bundled local options.
* Existing canonical fallback ids: `ft_technical_founder`, `ft_business_founder`, `ft_product_founder`, `ft_operator_founder`.

## Discovery Cards Endpoint

### Endpoint

```http
POST /api/v1/discovery/cards
```

### Request Shape

```json
{
  "context": {
    "mode": "finding_cofounder"
  },
  "filters": {},
  "pagination": {
    "limit": 10,
    "cursor": null
  }
}
```

### Request Rules

* `context.mode` is optional for default discovery, but recommended.
* `filters` is optional.
* `pagination.limit` maximum is `20`.
* `pagination.cursor` is nullable.
* Backend validates the contents of `filters` based on `context.mode`.
* Backend should reject unknown ids, invalid option ids, or premium-only filters without entitlement.

### Full Request Example: `joining_startups`

```json
{
  "context": {
    "mode": "joining_startups"
  },
  "filters": {
    "goalId": "goal_joining_startups",
    "startupStageIds": ["stage_mvp", "stage_seed"],
    "industryIds": ["ind_ai", "ind_fintech"],
    "founderTypeIds": ["ft_technical_founder", "ft_business_founder"],
    "locationAvailability": {
      "workArrangementIds": ["wa_remote", "wa_hybrid"],
      "remoteReady": true,
      "latitude": 37.785834,
      "longitude": -122.406417,
      "distanceKm": 50
    },
    "founderQuality": {
      "backgroundIds": ["fq_startup_experience", "fq_exit", "fq_fundraising_exposure"]
    },
    "leadershipStrength": {
      "leadershipIds": ["ls_built_team", "ls_led_product", "ls_growth_ownership"]
    },
    "startupReadiness": {
      "progressIds": ["jsr_mvp", "jsr_traction", "jsr_paying_users"]
    },
    "equityAndCommitment": {
      "termIds": ["eac_cofounder_equity", "eac_full_time_expected", "eac_pre_revenue_build"]
    }
  },
  "pagination": {
    "limit": 10,
    "cursor": null
  }
}
```

## Discovery Cards Response Contract

### Response Envelope

```json
{
  "success": true,
  "message": "Discovery cards fetched successfully",
  "data": {
    "items": [],
    "nextCursor": null,
    "hasMore": false
  }
}
```

### Profile Card Variant

Returned for `finding_cofounder` and `building_team`.

Required shape includes:

* `entityType: "profile"`
* `id`, `profileId`, `photoUrl`, `name`, `age`, `headline`
* `location: { city, country, display, distanceKm }`
* `match: { score, label }`
* `badges: [{ id, label, icon }]`
* `bio`, `startupIdea`
* `interests`, `skills`, `experience`, `education`, `languages`

### Startup Card Variant

Returned for `explore_startups` and `joining_startups`.

Required shape includes:

* `entityType: "startup"`
* `id`, `startupId`, `name`, `logoUrl`
* `badge: { label }`
* `founder: { name, title }`
* `match: { score, label }`
* `industry: { primary, secondary, display }`
* `team: { memberCount, display }`
* `summary`, `openRoles`, `lookingFor`, `teamStage`, `journey`

## Swipe Action Contract

### Endpoint

```http
POST /api/v1/discovery/cards/:targetId/action
```

### Request

```json
{
  "action": "like"
}
```

Allowed values:

* `like`
* `pass`
* `super_like`

### Response

```json
{
  "success": true,
  "message": "Swipe action recorded successfully",
  "data": {
    "id": "card_001",
    "targetId": "usr_ardi_001",
    "profileId": "usr_ardi_001",
    "startupId": null,
    "action": "like",
    "isMatch": true,
    "matchId": "match_123",
    "conversationId": "conv_123"
  }
}
```

For startup targets, `profileId` is `null` and `startupId` is populated.

## Validation Notes

* `pagination.limit` maximum is `20`.
* Do not return the current user in discovery results.
* Do not return targets already swiped by the current user.
* Backend computes `match.score`.
* Backend computes `location.distanceKm`.
* `targetId` is the route identifier for the discovery target.
* `profileId` is populated only for profile-card actions.
* `startupId` is populated only for startup-card actions.
* `id` remains the discovery card/session item id.
* Unknown filter ids, field ids, or option ids should be rejected.
* Premium-only filters should be rejected for unauthorized users with `PREMIUM_REQUIRED`.
* Backend should validate filters by `context.mode`.

## Known Errors

### Premium filters without entitlement

```json
{
  "success": false,
  "message": "Premium subscription required to use advanced discovery filters",
  "error": {
    "code": "PREMIUM_REQUIRED"
  }
}
```

### Super like boost requirement

See `CON-64` for the dedicated `super_like` behavior and boost-specific error handling.

### Rewind behavior

See `CON-65` for the dedicated rewind contract.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-60/define-api-contract-for-swipe-discovery-card-stack](https://linear.app/summondev/issue/CON-60/define-api-contract-for-swipe-discovery-card-stack)
- Identifier: CON-60
- Status: Backlog
- Priority: Medium
- Assignee: Unassigned
- Labels: API Contract, Backend
- Related issues: CON-65, CON-64
- Created: 2026-04-12T15:11:34.356Z
- Updated: 2026-05-04T02:03:25.602Z

## Comments

- Dwiki S:

  Contract amendment for grouped fetched catalogs only. Everything else in `CON-60` can stay as-is.

  ## Update Summary

  Please update the contract from fully frontend-owned filter options to a hybrid model:

  * Discovery filter section structure/order remains frontend-owned.
  * Shared grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` are backend-provided.
  * `POST /api/v1/discovery/cards` request shape stays the same.
  * Frontend still sends only selected ids/values when the user presses `Generate Candidates`.

  ## Summary section

  Replace the current frontend-owned filter wording with:

  * Discovery filter section structure and UI behavior are frontend-owned.
  * Shared grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` are backend-provided.
  * The backend contract owns:
    * `GET /api/v1/discovery/filter-options`
    * `POST /api/v1/discovery/cards`
    * `POST /api/v1/discovery/cards/:targetId/action`

  ## Ownership Model

  ### Frontend owns

  * Filter section structure
  * Filter section order
  * Filter section labels and descriptions
  * Local premium/locked presentation
  * Visual-only UI behavior such as icons, collapse defaults, helper copy, and search UX

  ### Backend owns

  * Accepted request schema
  * Canonical ids used in filter payloads
  * Validation of mode-specific filter payloads
  * Grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds`
  * Group labels and option labels for those fetched catalogs
  * Premium enforcement
  * Ranked discovery results
  * Match score calculation
  * Distance calculation
  * Excluding the current user and already-swiped targets

  ## Important rule

  Replace this:

  * There is no `GET /api/v1/discovery/filter-options` endpoint in this contract.

  With this:

  * The frontend ships the filter section configuration locally.
  * The frontend fetches grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` from `GET /api/v1/discovery/filter-options`.
  * The frontend still sends only selected ids/values in `POST /api/v1/discovery/cards`.

  ## New endpoint

  ### Endpoint

  ```http
  GET /api/v1/discovery/filter-options
  ```

  ### Response

  ```json
  {
    "success": true,
    "message": "Discovery filter options fetched successfully",
    "data": {
      "industries": [
        {
          "id": "grp_core_technology",
          "label": "Core Technology",
          "options": [
            { "id": "ind_ai", "label": "AI" },
            { "id": "ind_fintech", "label": "Fintech" }
          ]
        }
      ],
      "skills": [
        {
          "id": "grp_engineering",
          "label": "Engineering",
          "options": [
            { "id": "skill_react", "label": "React" },
            { "id": "skill_python", "label": "Python" }
          ]
        }
      ],
      "roles": [
        {
          "id": "grp_product_build",
          "label": "Product & Build",
          "options": [
            { "id": "role_engineer", "label": "Engineer" },
            { "id": "role_product", "label": "Product" }
          ]
        }
      ],
      "languages": [
        {
          "id": "grp_global_languages",
          "label": "Languages",
          "options": [
            { "id": "lang_en", "label": "English" },
            { "id": "lang_id", "label": "Bahasa Indonesia" }
          ]
        }
      ]
    }
  }
  ```

  ## Frontend Filter Configuration Reference

  Keep the existing section structure and canonical ids reference, but add one clarification near the top of that section:

  * `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` are catalog-backed fields.
  * Their option lists are fetched from `GET /api/v1/discovery/filter-options`.
  * The ids listed in this document remain canonical reference material for request validation.

  ## Request Rules / Validation Notes

  Add these clarifications:

  * Backend validates submitted `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` against the current grouped catalog response.
  * Backend should reject unknown, stale, or unsupported catalog ids.
  * Backend still validates filters by `context.mode` and premium entitlement.

  This keeps the existing `POST /cards` and swipe contract unchanged while moving only the shared option catalogs to a grouped fetched endpoint.

- Dwiki S:

  Implementation update aligned with the shipped frontend change. This is a surgical contract delta only, so the existing cards/swipe sections can remain unchanged.

  ## Contract delta to merge into `CON-60`

  ### Summary

  Change discovery filter ownership from fully frontend-owned options to a hybrid model:

  * Discovery filter section structure and UI behavior remain frontend-owned.
  * Shared grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` are backend-provided.
  * The backend contract now owns:
    * `GET /api/v1/discovery/filter-options?mode=<DiscoveryMode>`
    * `POST /api/v1/discovery/cards`
    * `POST /api/v1/discovery/cards/:targetId/action`

  ### Ownership Model

  Update the ownership bullets to:

  #### Frontend owns

  * Filter section structure
  * Filter section order
  * Filter section labels and descriptions
  * Local premium/locked presentation
  * Visual-only UI behavior such as icons, collapse defaults, helper copy, and search UX

  #### Backend owns

  * Accepted request schema
  * Canonical ids used in filter payloads
  * Validation of mode-specific filter payloads
  * Grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds`
  * Group labels and option labels for those fetched catalogs
  * Premium enforcement
  * Ranked discovery results
  * Match score calculation
  * Distance calculation
  * Excluding the current user and already-swiped targets

  ### Important rule

  Replace the current “no GET filter-options endpoint” rule with:

  * The frontend ships the filter section configuration locally.
  * The frontend fetches grouped catalogs for `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` from `GET /api/v1/discovery/filter-options?mode=<DiscoveryMode>`.
  * The frontend still sends only selected ids/values in `POST /api/v1/discovery/cards`.

  ### New endpoint

  ```http
  GET /api/v1/discovery/filter-options?mode=building_team
  ```

  Rules:

  * `mode` is required.
  * Response always includes `industries`, `skills`, `roles`, and `languages`.
  * Unused catalogs return `[]` and are never omitted.
  * `roleNeededIds` is scoped per mode, not a global superset.

  Example response shape:

  ```json
  {
    "success": true,
    "message": "Discovery filter options fetched successfully",
    "data": {
      "mode": "building_team",
      "industries": [
        {
          "id": "grp_industry_core_technology",
          "label": "Core Technology",
          "options": [
            { "id": "ind_ai", "label": "AI" },
            { "id": "ind_web3", "label": "Web3" }
          ]
        }
      ],
      "skills": [
        {
          "id": "grp_skill_engineering",
          "label": "Engineering",
          "options": [
            { "id": "skill_react", "label": "React" },
            { "id": "skill_python", "label": "Python" }
          ]
        }
      ],
      "roles": [
        {
          "id": "grp_role_product_engineering",
          "label": "Product & Engineering",
          "options": [
            { "id": "role_engineer", "label": "Engineer" },
            { "id": "role_product", "label": "Product" }
          ]
        }
      ],
      "languages": [
        {
          "id": "grp_language_global",
          "label": "Languages",
          "options": [
            { "id": "lang_en", "label": "English" },
            { "id": "lang_id", "label": "Bahasa Indonesia" }
          ]
        }
      ]
    }
  }
  ```

  ### Frontend Filter Configuration Reference

  Keep the existing section structure and canonical ids list, but add this clarification near the top of that section:

  * `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` are catalog-backed fields.
  * Their option lists are fetched from `GET /api/v1/discovery/filter-options?mode=<DiscoveryMode>`.
  * The ids already documented in `CON-60` remain canonical request ids for backend validation.

  ### Request Rules / Validation Notes

  Add these bullets:

  * Backend validates submitted `industryIds`, `skillIds`, `roleNeededIds`, and `languageIds` against the current grouped catalog for the requested `mode`.
  * Backend should reject unknown, stale, or unsupported catalog ids.
  * Backend still validates premium-only filters and all non-catalog fields exactly as documented today.

  ## Frontend implementation shipped against this delta

  * New discovery filter-options fetch path exists in the frontend service layer.
  * Dev/mock mode uses JSON-backed mock responses with the same wire shape as the contract.
  * Catalog-backed fields are injected into local filter section config at runtime.
  * Existing `POST /api/v1/discovery/cards` request payload shape is unchanged.

- Dwiki S:

  Backend update needed for [CON-60](https://linear.app/summondev/issue/CON-60/define-api-contract-for-swipe-discovery-card-stack): add city to discovery filter options and candidate generation filters.

  This is the only new contract change from this note.

  ## 1\. Filter Options Response

  Endpoint:

  ```http
  GET /api/v1/discovery/filter-options?mode=finding_cofounder
  ```

  Add `data.city` to the response:

  ```json
  {
    "success": true,
    "message": "Discovery filter options fetched successfully",
    "data": {
      "mode": "finding_cofounder",
      "city": {
        "id": "q_city",
        "type": "searchable_dropdown",
        "placeholder": "Search a city",
        "required": true,
        "meta": { "searchable": true },
        "options": [
          { "id": "opt_city_jakarta", "label": "Jakarta", "value": "jakarta", "group": "Indonesia" },
          { "id": "opt_city_bandung", "label": "Bandung", "value": "bandung", "group": "Indonesia" },
          { "id": "opt_city_singapore", "label": "Singapore", "value": "singapore", "group": "Singapore" },
          { "id": "opt_city_bangalore", "label": "Bangalore", "value": "bangalore", "group": "India" },
          { "id": "opt_city_hcmc", "label": "Ho Chi Minh City", "value": "hcmc", "group": "Vietnam" },
          { "id": "opt_city_dubai", "label": "Dubai", "value": "dubai", "group": "United Arab Emirates" }
        ]
      }
    }
  }
  ```

  Important:

  * Do not include a city question label like `Where are you based?`.
  * `options[].value` is the canonical value the frontend sends back.
  * `options[].group` is used by the frontend to group cities by country.

  ## 2\. Generate Candidates Request

  Endpoint:

  ```http
  POST /api/v1/discovery/cards
  ```

  When a user selects a city, frontend sends it here:

  ```json
  {
    "context": {
      "mode": "finding_cofounder"
    },
    "filters": {
      "goalId": "goal_finding_cofounder",
      "locationAvailability": {
        "workArrangementIds": ["wa_remote"],
        "city": "jakarta"
      }
    },
    "pagination": {
      "limit": 10,
      "cursor": null
    }
  }
  ```

  Backend should validate `filters.locationAvailability.city` against the supported city option values for the selected mode.

---

## Backend Implementation Status
**Status: ✅ Done**

| Sub-feature | Status | Catatan |
|---|---|---|
| `GET /api/v1/discovery/filter-options?mode=<mode>` | ✅ Done | Return `city`, `industries`, `skills`, `roles`, `availability`, `equity`, `languages` per mode |
| `POST /api/v1/discovery/cards` | ✅ Done | Paginated, cursor-based, mode-aware |
| `POST /api/v1/discovery/cards/:targetId/action` | ✅ Done | `like`, `pass`, `super_like` |
| City filter di `filter-options` | ✅ Done | 200+ kota Indonesia + Asia + global, grouped per region |
| City filter di `POST /cards` | ✅ Done | `filters.city` atau `filters.locationAvailability.city` |
| Exclude swiped users | ✅ Done | Exclude via `likes` + `user_matches` table |
| Exclude self dari feed | ✅ Done | |
| Premium filter validation | ✅ Done | Return `PREMIUM_REQUIRED` untuk non-pro users |
| Match score calculation | ✅ Done | Algoritma berbasis tag compatibility |
| `entityType: "profile"` | ✅ Done | Mode `finding_cofounder`, `building_team` |
| `entityType: "startup"` | ⚠️ Partial | Mode `explore_startups`, `joining_startups` — startup query ada, perlu validasi data startup di DB |
| `conversationId` di swipe response saat match | ✅ Done | Fix: sebelumnya tidak di-return ke FE |
| UUID validation pada `targetId` | ✅ Done | Guard: return 422 jika `targetId` bukan UUID (misal `card_xxxx`) |

**Penting untuk FE:**
- `:targetId` di URL harus pakai `profileId` (UUID) dari response cards, **bukan** field `id` (`card_xxxx`)
- Saat mutual match: response include `isMatch: true`, `matchId`, `conversationId` → FE langsung navigate ke chat room

**Matchmaking & Chat Implementation Status:**
**Status: ✅ Done**
- Mutual match detection via `likes` table.
- Auto-create conversation saat mutual match.
- `GenerateMatchAnalysisJob` (Async).
- Full Chat System (5 endpoints: list, message history, send, mark read, media gallery).
- FCM push notification enabled.