# Beha — Phase 1 Document 08: Development Roadmap

> Spec §38. Twelve-phase incremental plan. Each phase ships reviewable, testable artifacts. **No phase is built before the previous one is approved.**

---

## Phase 0 — Project Bootstrap

**Status: ✅ DONE in this delivery.**

- [x] Laravel project skeleton created
- [x] `composer.json`, `package.json`, `.env.example`, `.gitignore`, `artisan`, `bootstrap/app.php`
- [x] Config files: `app.php`, `auth.php`, `database.php`, `sanctum.php`, `permission.php`, `beha.php`, `workflows.php`, `evaluation.php`
- [x] Modular directory structure (`app/Services/{ID,Evaluation,Workflow,...}`)
- [x] Phase 1 design docs (`docs/01..08`)

**Deliverables:** Project repo + design documents.

---

## Phase 1 — Architecture & Design

**Status: ✅ DONE in this delivery.**

- [x] System architecture (`docs/01-system-architecture.md`)
- [x] Database ERD (`docs/02-database-erd.md`)
- [x] RBAC permission matrix (`docs/03-rbac-permission-matrix.md`)
- [x] Workflow diagrams (`docs/04-workflow-diagrams.md`)
- [x] Database schema (`docs/05-database-schema.md`)
- [x] UI/UX structure (`docs/06-ui-ux-structure.md`)
- [x] ID generation design (`docs/07-id-generation-design.md`)
- [x] Roadmap (`docs/08-development-roadmap.md`)

**Approval required before Phase 2.**

---

## Phase 2 — Laravel Foundation

**Goal:** A runnable Laravel app with auth, RBAC, base layout, dashboard framework.

- [ ] `composer install` + `npm install` succeed
- [ ] Database migrations for users, RBAC, sessions, password_resets, personal_access_tokens
- [ ] `RbacSeeder` creates 9 roles + all permissions
- [ ] `AdminSeeder` creates default System Administrator with temp password
- [ ] Login / logout / password reset
- [ ] Force-password-change middleware + page
- [ ] Base Blade layout (`layouts/app.blade.php`) with sidebar + topnav + footer
- [ ] Role-aware dashboard placeholder
- [ ] Tailwind + Vite build pipeline
- [ ] Sanctum API auth endpoints (`POST /api/v1/auth/token`)
- [ ] Audit middleware for sensitive actions
- [ ] PHPUnit/Pest setup + first feature tests

**Deliverables:** Working Laravel app, login, dashboard shell, API auth.

---

## Phase 3 — Organization Module

**Goal:** Generations, Branches, Teams, Team Members, user levels.

- [ ] Migrations: generations, branches, teams, team_members, user_levels
- [ ] Models with relations + scopes (e.g. `User::inSubtreeOf()`)
- [ ] `Organization\HierarchyService` — tree queries, ancestor/descendant checks
- [ ] `Organization\CapacityService` — validates `team_members_per_team`, `teams_per_branch`, `branches_per_generation` (configurable)
- [ ] CRUD controllers + Livewire tables + policies (role-scoped reads)
- [ ] Interactive org hierarchy view (`<x-beha-org-tree>`)
- [ ] Tests: capacity enforcement, hierarchy scoping

**Deliverables:** Manageable organization tree, capacity validation, hierarchy visualization.

---

## Phase 4 — Identity Module

**Goal:** All four ID types working.

- [ ] Migrations: id_sequences, id_generation_history
- [ ] `IdSequenceService` (lock + advance with `SELECT FOR UPDATE`)
- [ ] `OfficialIdGenerator`, `ConfidentialIdGenerator`, `CustomerReferenceGenerator`, `AssetCodeGenerator`
- [ ] `UserPolicy::viewConfidential` + Blade directive `@can('viewConfidential', $user)`
- [ ] `UserResource` omits `confidential_id` for unauthorized
- [ ] Configuration UI for ID formats (admin only)
- [ ] Audit log entries for every ID generation
- [ ] Tests: concurrency, year rollover, format substitution, authorization

**Deliverables:** All ID generators, sequence management, server-side confidential access control.

---

## Phase 5 — Applicant Onboarding

**Goal:** Public signup → screening → assignment → ID generation → account creation → first-login flow.

- [ ] Migrations: applicants, applicant_documents, applicant_reviews, applicant_assignments
- [ ] Public applicant signup page (no auth)
- [ ] Applicant workflow (config `applicant_onboarding`)
- [ ] Team Leader screening screen
- [ ] Branch Leader assignment screen (with capacity validation)
- [ ] Record Officer ID generation screen
- [ ] System Administrator account creation screen
- [ ] Temp password generation (cryptographically secure)
- [ ] Force-password-change middleware enforcement
- [ ] Email notifications at each step
- [ ] Tests: full workflow E2E, self-approval prevention, capacity rejection

**Deliverables:** End-to-end applicant onboarding.

---

## Phase 6 — Customer Management

**Goal:** Customer registration workflow with duplicate detection.

- [ ] Migrations: customers, customer_documents, customer_references, customer_reviews, customer_duplicates
- [ ] Customer workflow (config `customer_registration`)
- [ ] `Customer\DuplicateDetector` (configurable matching rules)
- [ ] Team Member create / submit form (multi-step)
- [ ] Team Leader evaluation screen
- [ ] Record Officer approval screen (with duplicate resolution)
- [ ] Customer reference code generation on registration
- [ ] Customer list / detail / search / filters
- [ ] Tests: duplicate detection, reference code immutability, workflow transitions

**Deliverables:** Complete customer lifecycle.

---

## Phase 7 — Property Management

**Goal:** Property registration → verification → asset coding → publication.

- [ ] Migrations: properties, property_documents, property_images, property_verifications, property_assets, property_publications
- [ ] Property workflow (config `property_registration` + `property_publication`)
- [ ] Generation Leader create / submit form
- [ ] Executive Officer verification screen
- [ ] Record Officer asset coding screen
- [ ] Publication toggle
- [ ] Public property portal (`/properties`) — published properties only, no internal data
- [ ] Image gallery with responsive conversions (spatie/laravel-medialibrary)
- [ ] Tests: verification flow, asset code immutability, publication gating

**Deliverables:** Full property pipeline + public portal.

---

## Phase 8 — Evaluation & Promotion

**Goal:** Configurable evaluation engine + promotion workflow.

- [ ] Migrations: evaluation_templates, evaluation_criteria, evaluations, evaluation_scores, evaluation_evidence, level_promotions
- [ ] `Evaluation\EvaluationEngine` — load template, score criteria, weighted score, decision
- [ ] `Evaluation\PromotionService` — issue promotion, update level, update role (if applicable), recalc rank
- [ ] Admin UI for evaluation templates + criteria (CRUD)
- [ ] Team Leader evaluation screen (per team member)
- [ ] Branch Leader approval screen
- [ ] Promotion workflow (config `promotion`)
- [ ] Tests: weighted scoring, passing threshold, promotion side effects

**Deliverables:** Flexible evaluation engine, promotion automation.

---

## Phase 9 — Performance & Ranking

**Goal:** Performance records + ranking + recalculation.

- [ ] Migrations: performance_records, performance_rankings, performance_ranking_history
- [ ] `Performance\ScoringService` — aggregate metrics per team member
- [ ] `Performance\RankingService` — rank within team / branch / generation
- [ ] Event-driven recalculation (on customer approval, evaluation finalization, sales record)
- [ ] Nightly batch (cron) as safety net
- [ ] Performance dashboard (personal + admin)
- [ ] Tests: ranking stability, recalculation triggers, history retention

**Deliverables:** Live performance scoring + ranking.

---

## Phase 10 — Finance Module

**Goal:** Foundation for revenue / sales / commission / payments.

> **Note (spec §38 Phase 9 + §32):** Finance forms and business rules will be supplied later. This phase establishes the schema + service interfaces; concrete forms added when requirements arrive.

- [ ] Migrations: sales, payments, commissions, invoices (placeholder — refined when forms arrive)
- [ ] `Finance` service interfaces (not implementations yet)
- [ ] Finance dashboard (read-only rollups based on what's available)
- [ ] Reports: revenue, outstanding balances, commission summary

**Deliverables:** Finance scaffolding; concrete forms added iteratively.

---

## Phase 11 — Reporting & Exports

**Goal:** Comprehensive reporting across all modules.

- [ ] `Reporting` service layer (query + format)
- [ ] Report definitions for: organization, customers, properties, employees, finance
- [ ] Chart.js dashboards for each role
- [ ] Export to Excel (`maatwebsite/excel`) and PDF (`barryvdh/laravel-dompdf`)
- [ ] Queued exports (large datasets)
- [ ] Filter / search UI
- [ ] Saved report templates (per user)

**Deliverables:** Production-grade reporting.

---

## Phase 12 — Security Hardening & Testing

**Goal:** Penetration-test-ready.

- [ ] Authorization tests (every endpoint)
- [ ] Authentication tests (login, lockout, password reset, temp password)
- [ ] File upload tests (mime validation, size limits)
- [ ] API security tests (Sanctum tokens, abilities)
- [ ] Confidential ID exposure tests (audit every API + Blade route)
- [ ] Self-approval prevention tests
- [ ] Workflow transition tests (every step)
- [ ] Audit log completeness tests
- [ ] Rate-limiting tests
- [ ] Browser tests (Laravel Dusk) for critical flows
- [ ] Deployment documentation
- [ ] Security documentation
- [ ] User + administrator manuals

**Deliverables:** Test-coverage, deployment docs, security audit.

---

## Branch Strategy

| Branch | Purpose |
|--------|---------|
| `main` | Production-ready. Only tagged releases. |
| `develop` | Integration branch — phase deliverables merged here first. |
| `phase/N-{name}` | One branch per phase (e.g. `phase/3-organization`). |
| `feature/{slug}` | One branch per feature within a phase. |
| `hotfix/{slug}` | Urgent fix off `main`. |

PRs require at least one review + passing CI (Pest tests + Larastan level 6).

---

## Release Tags

| Tag | Includes |
|-----|---------|
| `v0.1.0-phase1` | Design docs + Laravel scaffold (this delivery). |
| `v0.2.0-phase2` | Laravel foundation: auth, RBAC, dashboard. |
| `v0.3.0-phase3` | Organization module. |
| `v0.4.0-phase4` | Identity module (ID generation). |
| `v0.5.0-phase5` | Applicant onboarding. |
| `v0.6.0-phase6` | Customer management. |
| `v0.7.0-phase7` | Property management + public portal. |
| `v0.8.0-phase8` | Evaluation + promotion. |
| `v0.9.0-phase9` | Performance + ranking. |
| `v1.0.0-rc` | Phase 10–12 complete; release candidate. |
| `v1.0.0` | Production release. |

---

## Iteration Rule (spec §39)

For every new form / workflow added later, identify before coding:

1. Form fields (required + optional)
2. Who creates it
3. Who reviews it
4. Who approves it
5. Who can edit / reject / view it
6. Workflow states
7. Documents
8. Notifications
9. Audit requirements
10. Database relationships
11. Reports

**Never invent business rules where requirements have not been provided.**
