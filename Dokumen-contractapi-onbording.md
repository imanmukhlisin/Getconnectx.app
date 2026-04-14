Work on Linear issue CON-48:

<issue identifier="CON-48">
<title>[API Contract] Dynamic Onboarding Engine - Flow, Steps, Questions, Options</title>
<description>
# Dynamic Onboarding Engine — API Contract

## Goal

Backend stores **all onboarding flows, steps, questions, and options**. Frontend is a **dumb renderer** — it requests the next step, displays whatever the BE returns, collects answers, and posts them back. BE controls all branching logic.

This means:

* Adding/changing a question = BE config change only (no FE release)
* Adding a new flow (e.g., new user type) = BE only
* FE only needs to know how to render question types (text, select, multi-select, etc.)

---

## Flows Identified (from screenshots)

### Common: Data Diri (all flows start here)

1. What's your name? (First Name required, Last Name optional)
2. When's your date of birth?
3. Where are you based? (city dropdown, open to remote Y/N, remote preference)
4. Gender

### Flow Branching

```
How do you want to use ConnectX?
├── 1. I'm a Builder
│   ├── Founder                       -> FLOW 1
│   │   ├── Looking for Co-Founder      → Sub Flow A
│   │   ├── Looking for Team Members    → Sub Flow B
│   │   └── Looking for Both            → Sub Flow C
│   ├── Co-Founder (join a startup)   → Flow 2
│   └── Team Member (join a startup)  → Flow 3
└── 2. I represent a Startup  
```

### Questions per flow

**Common steps (all flows after Data Diri):**

* Prior startup experience? (Built/Founded/Worked in/No experience)
* Industries interest (multi-select, up to 5, searchable)
*  Availability (Full-time/Part-time/Flexible)
* Location based (Remote, Jakarta, Singapore, Bangalore, HCMC, Dubai, Anywhere)
* Open to remote? (Yes/No) + preference (Hybrid/Onsite/Remote preferred/Remote only)
* Willing to relocate? (Yes/No)
* Which role best describes you primarily? (dropdown + years of experience)
* LinkedIn URL
* "You're all set!" completion

**Flow A (Founder → Co-Founder):** What are you looking for = Co-Founder; What kind of co-founder do you need? (Technical, Business, Product, Growth)

**Flow B (Founder → Team Member):** What are you looking for = Team Members; What roles do you need? (CTO/Technical Lead, Product Designer, Growth Marketer, Full-Stack Engineer, Operations Lead, etc.)

**Flow C (Founder → Both):** Combined: co-founder types + team roles

**Flow D (Co-Founder joining):** What type of co-founder are you?; Cash-equity expectation; Minimum salary (strict/flexible/no minimum); Currency + Amount

**Flow E (Team Member joining):** What's your skillset?; Cash-equity expectation; Minimum salary; Currency + amount

**Flow F (Startup):** Tell us about your startup (Name + Stage: Idea/MVP/Live); What are you looking for; What industry is your startup in; What kind of co-founder/roles; Cash-equity offered; Minimum salary offered

---

## Core Concept: Form Engine

Each API response returns a **Step** containing 1+ **Questions**. FE renders the step, collects answers, POSTs back. BE computes next step based on branching rules.

### Step object

```json
{
  "id": "step_personal_name",
  "flow_key": "common_data_diri",
  "section": "Let's build your general profile",
  "section_progress": "1/4",
  "overall_progress": { "current": 1, "total": 12 },
  "title": "What's your name?",
  "subtitle": null,
  "questions": [
    {
      "id": "q_first_name",
      "type": "text",
      "label": "First Name",
      "required": true,
      "validation": { "min_length": 1, "max_length": 50 }
    },
    {
      "id": "q_last_name",
      "type": "text",
      "label": "Last Name",
      "required": false,
      "helper_text": "Last name is optional, and only shared after connection"
    }
  ],
  "cta": { "label": "Continue", "enabled_when": "valid" },
  "can_go_back": true
}
```

### Question types (FE must support)

| Type | Description |
| -- | -- |
| text | Single line text input |
| textarea | Multi-line text |
| number | Number input |
| date | Date picker |
| email | Email input |
| url | URL input |
| phone | Phone number |
| single_select_card | Cards with single selection (auto-advance optional) |
| single_select_radio | Radio buttons |
| multi_select_card | Cards with multi selection |
| multi_select_chip | Chips multi selection |
| dropdown | Dropdown list |
| searchable_dropdown | Dropdown with search + grouped options |
| grouped_list | List with section headers |
| currency_amount | Currency selector + amount input |
| segmented | Segmented control |

### Question object schema

```json
{
  "id": "string",
  "type": "single_select_card",
  "label": "string",
  "sub_label": "string (optional)",
  "helper_text": "string (optional)",
  "placeholder": "string (optional)",
  "required": true,
  "validation": {
    "min_length": 1,
    "max_length": 50,
    "min_selections": 1,
    "max_selections": 5
  },
  "options": [
    {
      "id": "opt_technical",
      "label": "Technical Co-Founder",
      "sub_label": "Engineering & architecture",
      "value": "technical",
      "icon": "icon_name",
      "group": "Advanced Technologies & Software"
    }
  ],
  "depends_on": {
    "question_id": "q_open_to_remote",
    "operator": "equals",
    "value": "yes"
  },
  "meta": {
    "auto_advance": true,
    "searchable": true,
    "layout": "grid_2"
  }
}
```

`depends_on` enables conditional rendering within a step (e.g., "What is your preference in working remotely?" only shows if "Are you open to working remotely?" = Yes).

---

## API Endpoints

### 1\. Start onboarding session

`POST /api/v1/onboarding/sessions`

Request: `{ "locale": "en" }`

Response 201:

```json
{
  "session_id": "ses_abc123",
  "status": "in_progress",
  "current_step": { }
}
```

### 2\. Get current step

`GET /api/v1/onboarding/sessions/:session_id/current`

Returns the current step (for resume or reload).

### 3\. Submit answer + get next step

`POST /api/v1/onboarding/sessions/:session_id/answer`

Request:

```json
{
  "step_id": "step_role_selection",
  "answers": {
    "q_use_connectx": "founder"
  }
}
```

Response 200 (next step):

```json
{
  "next_step": { },
  "progress": { "current": 3, "total": 12 },
  "can_go_back": true
}
```

Response 200 (completed):

```json
{
  "next_step": null,
  "completed": true,
  "profile_id": "prof_xyz789",
  "redirect_to": "/home"
}
```

Response 422 (validation error):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "q_first_name": [
      "'Nama Depan' terlalu pendek (Minimum 3 huruf)."
    ]
  }
}
```

### 4\. Go back

`POST /api/v1/onboarding/sessions/:session_id/back`

### 5\. Get full session state

`GET /api/v1/onboarding/sessions/:session_id`

### 6\. (Admin) Manage flows

Standard CRUD for flows/steps/questions/options. Can be built later.

---

## Database Schema

```sql
CREATE TABLE onboarding_flows (
  id VARCHAR PRIMARY KEY,
  name VARCHAR NOT NULL,
  description TEXT,
  is_entry BOOLEAN DEFAULT false,
  created_at TIMESTAMP
);

CREATE TABLE onboarding_steps (
  id VARCHAR PRIMARY KEY,
  flow_id VARCHAR REFERENCES onboarding_flows(id),
  order_index INT NOT NULL,
  section VARCHAR,
  title VARCHAR NOT NULL,
  subtitle VARCHAR,
  cta_label VARCHAR DEFAULT 'Continue',
  auto_advance BOOLEAN DEFAULT false,
  can_go_back BOOLEAN DEFAULT true
);

CREATE TABLE onboarding_questions (
  id VARCHAR PRIMARY KEY,
  step_id VARCHAR REFERENCES onboarding_steps(id),
  order_index INT NOT NULL,
  type VARCHAR NOT NULL,
  label VARCHAR NOT NULL,
  sub_label VARCHAR,
  helper_text VARCHAR,
  placeholder VARCHAR,
  required BOOLEAN DEFAULT false,
  validation JSONB,
  depends_on JSONB,
  meta JSONB
);

CREATE TABLE onboarding_options (
  id VARCHAR PRIMARY KEY,
  question_id VARCHAR REFERENCES onboarding_questions(id),
  order_index INT NOT NULL,
  label VARCHAR NOT NULL,
  sub_label VARCHAR,
  value VARCHAR NOT NULL,
  icon VARCHAR,
  group_name VARCHAR
);

CREATE TABLE onboarding_transitions (
  id SERIAL PRIMARY KEY,
  from_step_id VARCHAR REFERENCES onboarding_steps(id),
  condition JSONB,
  to_step_id VARCHAR REFERENCES onboarding_steps(id),
  to_flow_id VARCHAR REFERENCES onboarding_flows(id),
  priority INT DEFAULT 0
);

CREATE TABLE onboarding_sessions (
  id VARCHAR PRIMARY KEY,
  user_id VARCHAR REFERENCES users(id),
  current_step_id VARCHAR,
  status VARCHAR,
  started_at TIMESTAMP,
  completed_at TIMESTAMP
);

CREATE TABLE onboarding_responses (
  id SERIAL PRIMARY KEY,
  session_id VARCHAR REFERENCES onboarding_sessions(id),
  step_id VARCHAR,
  question_id VARCHAR,
  value JSONB,
  answered_at TIMESTAMP
);
```

---

## Branching Logic

**1. Linear (default):** Next step = current step's `order_index + 1` within same flow.

**2. Conditional transition:** If current step has entries in `onboarding_transitions`, evaluate in priority order:

```json
{
  "from_step_id": "step_role_selection",
  "condition": { "question_id": "q_use_connectx", "operator": "equals", "value": "startup" },
  "to_step_id": "step_startup_details",
  "priority": 1
}
```

Operators: `equals`, `not_equals`, `in`, `not_in`, `contains`, `exists`

**3. Flow jump:** A transition can point to a different flow's first step.

---

## Completion flow

When last step is submitted:

1. BE validates all responses
2. BE creates user profile from responses (mapping response values to profile fields)
3. BE marks session as `completed`
4. BE returns `next_step: null, completed: true, profile_id, redirect_to`
5. FE navigates to home

Response-to-profile mapping is stored BE-side.

---

## Implementation Phases

### Phase 1 (this sprint)

* Core engine: sessions, steps, questions, options, responses
* Seed 6 flows from screenshots
* 5 core endpoints (1-5)
* FE renders all question types

### Phase 2 (post-launch)

* Admin UI to manage flows
* Analytics: drop-off per step
* A/B testing support

---

## FE Implementation Guidelines (Agreements)

1. **Fetching Flow:** FE must NOT cache the flow. Always fetch per step (`POST /answer` returns the next exact step) because the backend branching logic is dynamic and can change on the fly.
2. **Auto-advance:** For `single_select_card` or `single_select_radio`, FE can optionally auto-execute the `POST /answer` on tap for a smoother UX (no need to press "continue").
3. **Searchable Dropdowns:** For options like Industries and Skills, **no pagination is needed**. Options are all loaded at once via the API (~90 items) and filtered locally (client-side search) on the frontend device.
4. **Flow Entry Point:** The "Data Diri" onboarding flow automatically triggers immediately right after a successful OTP verification / Signup.
5. **Optional Steps:** If a question has `required: false`, simply allow the user to click "Continue" without forcing an input. No explicit "Skip" button is needed.
6. **Localization (Language):** For MVP V1, text is served exactly as authored in the Database (currently a mix of ID/EN). FE does not need to implement client-side localizations for dynamic onboarding content.

---

## Acceptance

- [x] BE: all 6 flows seeded with correct steps, questions, options
- [x] BE: all 5 session endpoints working
- [x] BE: branching logic handles all 6 flows correctly
- [ ] FE: renders all question types
- [ ] FE: handles `depends_on` (conditional question display)
- [ ] FE: handles `auto_advance` on single-select cards
- [ ] E2E: user can complete each of the 6 flows and land on home with valid profile
</description>
<team name="ConnectX"/>
<label>Frontend</label>
<label>API Contract</label>
<label>Backend</label>
<project name="ConnectX App">Mobile-first swipe-based matching platform for startup founders, co-founders, and team members</project>
</issue>