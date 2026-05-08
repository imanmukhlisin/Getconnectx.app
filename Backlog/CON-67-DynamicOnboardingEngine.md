# [BE] Dynamic Onboarding Engine — Sessions, Step Engine & Answer Submission

## Context

The FE is a **dumb renderer** — it sends answers, BE decides what step comes next. No hardcoded flow logic on the frontend. All step content (labels, options, branching) is served by the BE.

FE contract reference: `CON-48`. This ticket is the implementation spec.

---

## Base URL & Auth

```
Base: /api/v1/onboarding
Auth: Bearer <JWT>
Locale: Accept-Language: "en" | "id"  (fallback: "en")
```

All strings (`title`, `subtitle`, `label`, `placeholder`, `helper_text`, option labels) must be **server-rendered in the requested locale**. FE does zero translation.

---

## Endpoints to implement

| Method | Path | Purpose |
| -- | -- | -- |
| POST | `/sessions` | Create or resume a session. Returns first step. |
| GET | `/sessions/:session_id` | Full session + current step (debug / resume-from-anywhere) |
| GET | `/sessions/:session_id/current` | Current step only — cheap on reload |
| POST | `/sessions/:session_id/answer` | Submit answers for current step |
| POST | `/sessions/:session_id/back` | Rewind one step |

---

## Step response shape (every step-returning endpoint)

```ts
{
  id: string
  flow_key: string
  section: string
  section_progress: string           // "2/4" — display only
  overall_progress: { current: number; total: number }
  title: string
  subtitle: string | null
  questions: Question[]
  cta: { label: string; enabled_when: "always" | "valid" }
  can_go_back: boolean
}
```

---

## Question shape

```ts
{
  id: string
  type: OnboardingQuestionType
  label: string
  sub_label?: string | null
  helper_text?: string | null
  placeholder?: string | null
  required: boolean
  options?: Option[]
  validation?: Validation
  meta?: Meta
  depends_on?: DependsOn
}
```

**Supported question types:**
`text`, `textarea`, `number`, `date`, `email`, `url`, `phone`, `single_select_card`, `single_select_chip`, `single_select_radio`, `dropdown`, `searchable_dropdown`, `searchable_single_select`, `segmented`, `grouped_list`, `multi_select_card`, `multi_select_chip`, `searchable_multi_select`, `currency_amount`

**Answer value by type:**

| Type | Stored value |
| -- | -- |
| text / textarea / email / url / phone | `string` |
| date | `string` (YYYY-MM-DD) |
| number | `number` |
| single_select\_\* / dropdown / segmented / grouped_list | `string` (option.value) |
| multi_select\_\* / searchable_multi_select | `string[]` |
| currency_amount | `{ amount: string; currency: string }` |

`depends_on` semantics: A question is only rendered/validated when its condition matches. BE must NOT validate or store hidden questions. Operators: `equals`, `not_equals`, `in`, `not_in`, `contains`, `exists`.

---

## POST `/sessions` — Create or resume

**Response 201:**

```json
{
  "session_id": "ses_01HSXYZABC123",
  "status": "in_progress",
  "current_step": { "...step object..." }
}
```

One `in_progress` session per mode (`preview` vs `post_auth`) max. Resume if exists, create if not.

---

## POST `/sessions/:id/answer` — Submit answers

**Request:**

```json
{
  "step_id": "step_data_diri",
  "answers": {
    "q_first_name": "Dio",
    "q_last_name": "Pratama",
    "q_date_of_birth": "1998-05-12",
    "q_city": "jakarta",
    "q_gender": "male"
  }
}
```

**Happy path → 200:**

```json
{
  "next_step": { "...step object..." },
  "progress": { "current": 3, "total": 9 },
  "can_go_back": true
}
```

**Final step → 200:**

```json
{
  "next_step": null,
  "completed": true,
  "profile_id": "prof_01HSXYZABC123",
  "redirect_to": "/(tabs)",
  "can_go_back": false
}
```

**Validation failure → 422:**

```json
{
  "error": "validation_failed",
  "errors": {
    "q_first_name": "First name is required.",
    "q_date_of_birth": "Use the YYYY-MM-DD format."
  }
}
```

`POST /answer` with same `step_id` + same answers must be **idempotent** (safe to retry, no double-advance).

---

## POST `/sessions/:id/back` — Rewind

**Response 200:**

```json
{
  "previous_step": { "...step object..." },
  "progress": { "current": 2, "total": 9 }
}
```

Step history is append-only except on `back`, which pops exactly one entry.

---

## Step catalog — branching logic

### Common (all users)

| Step ID | Questions |
| -- | -- |
| `step_welcome` | none — marketing panel, CTA always enabled |
| `step_data_diri` | `q_first_name`, `q_last_name` (optional), `q_date_of_birth`, `q_city`, `q_gender` |
| `step_use_connectx` | `q_use_connectx` ∈ {`builder`, `startup`} — auto_advance: true |
| `step_identity_details` | Builder: `q_builder_type`. Startup: `q_startup_name`, `q_startup_tagline`, `q_startup_stage` |

### Builder path (`q_use_connectx === "builder"`)

Branches by `q_builder_type`:

* **Founder** → `step_primary_role` → `step_founder_recruiting`
* **Cofounder / Team member** → `step_cofounder_specialty` → `step_skills`
* All paths converge at `step_work_preferences`

### Startup path (`q_use_connectx === "startup"`)

`step_startup_vision` → `step_startup_traction` (varies by `q_startup_stage`) → `step_startup_presence` → `step_startup_team` → `step_startup_recruiting`

---

## Session lifecycle

```
none ──(POST /sessions)──▶ in_progress
                                │
              POST /answer (has next step) → in_progress (new step)
              POST /answer (final step)   → completed
                                               │
                             materialize user_profiles row
                             set session.profile_id
```

Session row kept for audit after completion.

---

## DB

`onboarding_sessions`: `id`, `user_id`, `mode`, `status`, `flow_key`, `locale`, `current_step_id`, `step_history[]`, `answers` (JSON blob), `profile_id`, timestamps.

Store raw answers JSON during flow. Project to `user_profiles` / `startup_profiles` only on completion.

---

## Non-goals

* No WebSockets — strictly request/response.
* FE re-syncs via `GET /current` if step order disagrees — BE is authoritative.
* Do NOT validate or persist answers for questions hidden by `depends_on`.

## Metadata
- URL: [https://linear.app/summondev/issue/CON-67/be-dynamic-onboarding-engine-sessions-step-engine-and-answer](https://linear.app/summondev/issue/CON-67/be-dynamic-onboarding-engine-sessions-step-engine-and-answer)
- Identifier: CON-67
- Status: Backlog
- Priority: Urgent
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 2 - Onboarding Complete (target: 2026-04-10T17:00:00.000Z)
- Related issues: CON-48
- Due date: 2026-04-21T17:00:00.000Z
- Created: 2026-04-20T08:02:53.998Z
- Updated: 2026-04-21T17:20:02.993Z