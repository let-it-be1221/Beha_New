# Beha — Phase 1 Document 06: UI/UX Structure

> Spec §29, §42.F. Enterprise real-estate visual language. Responsive across desktop / laptop / tablet / mobile.

---

## 1. Design System

| Token | Value |
|-------|-------|
| Primary | `#0F3A5F` (deep navy — trust, finance) |
| Primary-600 | `#145374` (hover) |
| Accent | `#C8A24B` (warm gold — real-estate premium) |
| Success | `#15803D` |
| Warning | `#B45309` |
| Danger | `#B91C1C` |
| Neutral-900 | `#0F172A` (text) |
| Neutral-100 | `#F1F5F9` (surface) |
| Border | `#E2E8F0` |
| Font family (Latin) | Inter (UI) + Lora (headings) |
| Font family (CJK fallback) | Noto Sans SC |
| Radius | `0.5rem` (cards) / `0.25rem` (buttons/inputs) |
| Shadow | `0 1px 3px rgba(15,23,42,.08)` default, `0 4px 12px` lifted |
| Spacing scale | Tailwind default (4px base) |
| Density | Comfortable (default) / Compact (toggle on dashboards) |

**Stack:** Tailwind CSS 3 + Flowbite components + Filament 3 widgets + Alpine.js for interactions + Chart.js for charts.

---

## 2. Page Map

```text
/ (public landing — placeholder for property portal)
/login
/password/force-change      ← mandatory after first login
/password/reset
/applicants/apply           ← public applicant signup
/properties                 ← public portal (published properties only)

# Authenticated area
/dashboard                  ← role-aware dashboard
/me                         ← profile
/me/password                ← change password
/me/notifications           ← notification center
/me/audit                   ← own activity (limited)

# Admin (sys_admin)
/admin/users
/admin/roles
/admin/permissions
/admin/settings
/admin/audit-logs
/admin/id-sequences

# Organization
/organization/generations
/organization/branches
/organization/teams
/organization/team-members
/organization/hierarchy     ← interactive org tree
/organization/promotions

# Customers
/customers                  ← list (scoped by role)
/customers/create
/customers/{id}             ← detail with timeline
/customers/{id}/edit
/customers/{id}/documents
/customers/{id}/reviews
/customers/{id}/approve
/customers/{id}/reject
/customers/duplicates

# Properties
/properties/admin           ← internal list (scoped by role)
/properties/admin/create
/properties/admin/{id}      ← detail with timeline
/properties/admin/{id}/edit
/properties/admin/{id}/verify
/properties/admin/{id}/assign-asset-code
/properties/admin/{id}/publish
/properties/admin/{id}/documents
/properties/admin/{id}/images

# Applicants
/applicants                 ← list (scoped)
/applicants/{id}            ← detail
/applicants/{id}/screen     ← team leader screening
/applicants/{id}/assign     ← branch leader assignment
/applicants/{id}/generate-ids ← record officer
/applicants/{id}/create-account ← sys admin

# Evaluations
/evaluations/templates      ← list / edit (admin)
/evaluations                ← list (scoped)
/evaluations/create
/evaluations/{id}           ← detail with scores
/evaluations/{id}/score
/evaluations/{id}/finalize

# Performance
/performance                ← personal
/performance/rankings       ← admin / branch / gen
/performance/history

# Workflows
/workflows                  ← all in-progress instances I own or can act on
/workflows/{id}             ← instance timeline + comments + attachments

# Reports
/reports                    ← index of available reports
/reports/{type}             ← e.g. /reports/customers-by-team
/reports/exports            ← queue of running exports

# Notifications (global)
/notifications
```

---

## 3. Layout Anatomy

```text
┌─────────────────────────────────────────────────────────────────┐
│  [≡] Top Nav: Logo · Breadcrumb · Search · Notif · Profile ▾   │  ← 64px
├──────────┬──────────────────────────────────────────────────────┤
│          │                                                       │
│          │                                                       │
│ Sidebar  │              Page Content                             │
│ 240px    │              (cards, tables, charts)                 │
│          │                                                       │
│  - Dash  │                                                       │
│  - Org   │                                                       │
│  - Cust  │                                                       │
│  - Prop  │                                                       │
│  - App   │                                                       │
│  - Eval  │                                                       │
│  - Perf  │                                                       │
│  - Wf    │                                                       │
│  - Rep   │                                                       │
│  - Set   │                                                       │
│          │                                                       │
└──────────┴──────────────────────────────────────────────────────┘
Footer: © Beha · version · privacy · support
```

- **Mobile (< 768px):** sidebar collapses to drawer; top nav has hamburger; tables switch to card list.
- **Tablet (768–1024px):** sidebar shrinks to icons-only (96px).
- **Desktop (≥ 1024px):** full sidebar (240px) + content.
- **Wide (≥ 1536px):** content max-width `1536px` centered with breathing margins.

---

## 4. Component Library (custom Blade components)

| Component (`<x-beha-*>`)       | Purpose                                              |
|---------------------------------|------------------------------------------------------|
| `<x-beha-status-badge>`         | Color-coded status pill (configurable map)            |
| `<x-beha-card>`                 | Surface container with header / actions slot         |
| `<x-beha-data-table>`           | Sortable / paginated / filterable table              |
| `<x-beha-empty-state>`          | Icon + title + description + action button           |
| `<x-beha-breadcrumb>`           | Auto-rendered from route name                        |
| `<x-beha-stepper>`              | Multi-step form navigation                            |
| `<x-beha-timeline>`             | Workflow history visualization                       |
| `<x-beha-approval-panel>`       | Approve / Reject / Request correction + comment      |
| `<x-beha-org-tree>`             | Interactive hierarchical org chart                  |
| `<x-beha-stat-card>`            | KPI tile (label, value, trend %)                     |
| `<x-beha-chart>`                | Chart.js wrapper (bar / line / pie)                  |
| `<x-beha-modal>`                | Modal dialog (Alpine-powered)                        |
| `<x-beha-confirm-dialog>`       | Confirmation modal before destructive actions         |
| `<x-beha-upload>`               | Drag-drop file upload with mime/size validation       |
| `<x-beha-search-input>`         | Debounced search with global command palette (⌘K)     |
| `<x-beha-notification-center>`  | Slide-over notification list                         |
| `<x-beha-profile-menu>`         | Avatar + dropdown (profile, settings, logout)         |
| `<x-beha-confidential-field>`  | Field rendered as ••• unless viewer has permission   |

---

## 5. Login Screen

```text
┌────────────────────────────────────────────────┐
│                                                │
│   [Logo]                                       │
│   Beha — Real Estate Management                │
│                                                │
│   ┌──────────────────────────────────────┐    │
│   │ Email or Official ID                  │    │
│   └──────────────────────────────────────┘    │
│   ┌──────────────────────────────────────┐    │
│   │ Password                          👁  │    │
│   └──────────────────────────────────────┘    │
│                                                │
│   [        Sign in        ]                    │
│                                                │
│   Forgot password?                             │
│                                                │
│   ─── Applicant? ───                          │
│   [ Apply to join the organization ]           │
│                                                │
└────────────────────────────────────────────────┘
```

Behavior:
- Rate-limited (max 5 attempts / 15 min) — `TrackLoginAttempts` middleware.
- On first login (must_change_password=true), redirect to `/password/force-change`.
- Show non-committal error messages ("Invalid credentials") — never reveal whether email exists.

---

## 6. Dashboard Skeleton (per role)

### System Administrator Dashboard

```text
[ Total Users ]  [ Active Users ]  [ Pending Apps ]  [ Audit Alerts ]
[ Teams ]        [ Branches ]      [ Generations ]   [ Customers ]  [ Properties ]

[ Pending Workflows — grouped by type ]
[ Recent Audit Log — last 20 actions ]
[ System Health — queue depth, failed jobs, cache hit ]
```

### Executive Dashboard

```text
[ Properties Awaiting Verification ]   [ Approved Properties ]
[ Organizational Performance — top 3 generations ]
[ Customer Statistics — registered this month ]
[ Sales Statistics — YTD vs last year ]
[ Pending Approvals (executive queue) ]
```

### Record Officer Dashboard

```text
[ Customers Awaiting Approval ]  [ New Customers Today ]  [ Duplicate Candidates ]
[ Properties Awaiting Asset Coding ]  [ Pending ID Assignments ]
[ Reference Code Statistics — generated per day (chart) ]
```

### Generation / Branch / Team Leader Dashboard

```text
[ Members in scope ]  [ Active Members ]  [ Inactive Members ]
[ Customer Registrations — by member ]
[ Sales — by member ]
[ Performance — ranked members ]
[ Pending Evaluations ]  [ Pending Applicant Screening ]
```

### Team Member Dashboard

```text
Welcome, {name} — Level {1..5}
[ My Customers ]  [ Active ]  [ Pending ]  [ Rejected ]
[ My Sales — total value, count ]
[ My Performance — current rank in team ]
[ My Evaluation — last result, next due ]
[ Available Properties — carousel ]
[ Recent Notifications ]
```

---

## 7. Forms

All forms use:
- `<x-beha-card>` wrapper
- `<x-beha-stepper>` when multi-step (customer registration, property registration, applicant)
- Server-side validation via Form Requests
- Inline error display under each field
- Autosave drafts (where applicable) to allow resume
- File uploads via `<x-beha-upload>` with mime/size guard

### Customer Registration Form (multi-step stepper)

```text
Step 1: Personal         | Step 2: Contact       | Step 3: Address & Employment | Step 4: Documents
- Full name             | - Phone               | - Street address             | - National ID (file)
- Date of birth         | - Email               | - City / Region              | - Proof of income (file)
- National ID           | - Alt phone           | - Employment status          | - Other documents
- Gender                | - Preferred contact   | - Employer                   | - Notes
- Marital status        | - Customer source      | - Monthly income             | - Submit
```

### Property Registration Form

Single page with sections:
1. Basic info (name, type, location, GPS)
2. Specifications (size, units, beds, baths, floor)
3. Building info (developer, ownership, year built)
4. Pricing & availability
5. Description + amenities (chips)
6. Legal documents (uploads)
7. Images / videos (uploads)

---

## 8. Approval Screens

```text
┌──────────────────────────────────────────────────────┐
│  Customer: John Doe — CUS-2026-000123                  │  ← header
├──────────────────────────────────────────────────────┤
│  Timeline                                             │
│   ● Draft — submitted 2026-09-15                      │
│   ● Team Leader Review — Approved 2026-09-16          │
│   ● Record Officer — In progress                      │
│                                                        │
│  Applicant / Customer Details (read-only form)        │
│  Documents (downloadable list)                         │
│                                                        │
│  ┌── Approval Panel ──────────────────────────────┐   │
│  │ Comment: ┌────────────────────────────────┐   │   │
│  │          │                                  │   │   │
│  │          └────────────────────────────────┘   │   │
│  │ [ Approve ]  [ Reject ]  [ Request Correction ]│   │
│  └──────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────┘
```

Self-approval prevention: the panel hides if `current_owner === Auth::user()` and the page is unreachable via direct URL (Policy returns 403).

---

## 9. Organizational Hierarchy Visualization

Interactive tree component (`<x-beha-org-tree>`) using D3-like nested flexbox + Alpine for expand/collapse.

```text
Executive Officer
├── Generation 1
│   ├── Branch 1
│   │   ├── Team 1 — Leader: Jane Doe
│   │   │   ├── John Smith (L1) — 7 customers
│   │   │   └── ...
│   │   └── Team 2
│   └── Branch 2
└── Generation 2
```

Click any node → side panel with stats:
- Members count
- Active customers
- Sales YTD
- Pending evaluations
- Capacity utilization (e.g. 8/10 team members)

---

## 10. Empty / Loading / Error States

| State | Component | Behavior |
|-------|-----------|----------|
| Empty | `<x-beha-empty-state icon illustration title description action>` | Show illustration + relevant CTA |
| Loading | Skeleton + spinner overlay | Skeleton matches final layout shape |
| Error | `<x-beha-error-page code title message>` | 404 / 403 / 500 with helpful retry actions |
| Form validation | Inline errors below fields | Red border + helper text + aria-invalid |

---

## 11. Accessibility (WCAG 2.1 AA target)

- Semantic HTML (`<nav>`, `<main>`, `<section>`, `<article>`)
- All interactive elements keyboard-reachable; visible focus rings
- Color contrast ≥ 4.5:1 for text
- All form fields have `<label>`; errors associated via `aria-describedby`
- Modals trap focus while open; restore on close
- Skip-to-main-content link

---

## 12. Performance Targets

| Page | LCP target | TTI target |
|------|-----------|-----------|
| Login | < 1.0s | < 1.2s |
| Dashboard | < 1.5s | < 2.0s |
| Customer list (100 rows) | < 2.0s | < 2.5s |
| Property detail (10 images) | < 2.5s | < 3.0s |
| Org hierarchy (50 nodes) | < 1.5s | < 2.0s |

All pages served with HTTP/2 + gzip + brotli + browser caching for static assets. Images served via `spatie/laravel-medialibrary` with responsive conversions (avif / webp).
