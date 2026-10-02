# Beha — Phase 1 Document 01: System Architecture

> Master architecture for the Beha Real Estate Management System. Reviewed before any application code is written (spec §42.A).

---

## 1. High-Level Layered Architecture

```text
┌──────────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                            │
│  Web (Blade + Livewire 3 + Tailwind + Filament)  ·  REST API v1     │
│  Public portal  ·  Auth (login, password change)  ·  Dashboards    │
└──────────────────────────────┬───────────────────────────────────────┘
                               │
┌──────────────────────────────▼───────────────────────────────────────┐
│                          HTTP LAYER                                  │
│  Routes → Form Requests (validation) → Policies (authorization)     │
│  Middleware: role · permission · level · confidential · audit       │
│  rate-limiter · Sanctum (API) · force-password-change              │
└──────────────────────────────┬───────────────────────────────────────┘
                               │
┌──────────────────────────────▼───────────────────────────────────────┐
│                       APPLICATION LAYER                              │
│  Controllers (thin)  →  Actions (single-purpose)  →  Resources     │
│  Notifications  ·  Jobs (queued)  ·  Events / Listeners             │
└──────────────────────────────┬───────────────────────────────────────┘
                               │
┌──────────────────────────────▼───────────────────────────────────────┐
│                          DOMAIN LAYER                                │
│  Services:                                                            │
│    ID\OfficialIdGenerator  ·  ID\ConfidentialIdGenerator           │
│    ID\CustomerReferenceGenerator  ·  ID\AssetCodeGenerator          │
│    Workflow\WorkflowEngine  ·  Workflow\StateMachine                │
│    Evaluation\EvaluationEngine  ·  Evaluation\PromotionService      │
│    Performance\ScoringService  ·  Performance\RankingService        │
│    Customer\DuplicateDetector  ·  Customer\LifecycleService         │
│    Property\VerificationService  ·  Property\PublicationService   │
│    Organization\HierarchyService  ·  Organization\CapacityService │
│  Repositories (query / persistence boundaries)                     │
│  Enums  ·  Traits (Workflowable, Auditable, HasConfidentialId)     │
└──────────────────────────────┬───────────────────────────────────────┘
                               │
┌──────────────────────────────▼───────────────────────────────────────┐
│                       INFRASTRUCTURE LAYER                           │
│  Eloquent Models  ·  MySQL 8+  ·  Redis (cache, queue, session)      │
│  Laravel Storage (local / S3)  ·  Laravel Queues (Redis driver)     │
│  Laravel Mail  ·  spatie/laravel-activitylog (audit)                │
│  spatie/laravel-medialibrary (documents & media)                    │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 2. Request Flow (Customer Registration — example)

```text
1. Team Member submits form → POST /customers
2. Route → middleware: auth, role:team_member, audit
3. StoreCustomerRequest validates input (Form Request)
4. CustomerPolicy::create() verifies authorization (server-side)
5. Controller delegates to App\Actions\Customer\RegisterCustomer
6. Action wraps in DB transaction:
     a. Persist Customer (status = draft)
     b. Kick off WorkflowService::start('customer_registration', $customer)
     c. Dispatch App\Events\CustomerRegistered
     d. App\Listeners\NotifyTeamLeader sends DB + email notification
     e. App\Services\Audit\AuditLogger records action
7. Controller returns redirect → /customers/{id} with success flash
```

---

## 3. Module Map

| Module           | Path                                      | Description                                                                 |
|------------------|-------------------------------------------|-----------------------------------------------------------------------------|
| Identity         | `app/Services/ID/`                        | Official ID, Confidential ID, Reference & Asset Code generation + sequences |
| Workflow         | `app/Services/Workflow/`                  | Reusable workflow engine — steps, transitions, approvals, comments, history |
| Evaluation       | `app/Services/Evaluation/`                | Templates, criteria, scoring, weighted result, promotion decision          |
| Performance      | `app/Services/Performance/`               | Score aggregation + ranking + rank history                                 |
| Customer         | `app/Services/Customer/`                  | Lifecycle, duplicate detection, reference code binding                    |
| Property         | `app/Services/Property/`                  | Verification → asset coding → publication pipeline                         |
| Organization     | `app/Services/Organization/`             | Hierarchy + capacity validation + assignment                                |
| RBAC             | `app/Policies/` + Spatie permissions      | Server-side authorization (policies + middleware)                          |
| Audit            | `app/Services/Audit/`                     | Tamper-resistant audit log + sensitive-operation tracer                     |
| Notifications    | `app/Notifications/`                      | Database + email + (future) SMS / push                                      |
| Reporting        | `app/Services/Reporting/`                 | KPI rollups, exports (Excel + PDF)                                          |
| Configuration    | `config/beha.php`                         | All organization-specific business rules                                    |

---

## 4. Cross-Cutting Concerns

| Concern                | Implementation                                                                 |
|------------------------|-------------------------------------------------------------------------------|
| Authentication         | Laravel session guard (web) + Sanctum (API tokens for mobile)                |
| Authorization          | Spatie roles/permissions (DB-backed) + Laravel Policy classes per model      |
| Audit Logging          | Custom `AuditLog` model + `spatie/laravel-activitylog` for model changes      |
| Sensitive field encryption | Laravel `encrypt:` cast on `users.confidential_id` etc.                  |
| Mass assignment        | Explicit `$fillable` on every model — never `$guarded = []`                  |
| File upload security  | MIME validation + size limits + signed download URLs + permission-gated downloads |
| Rate limiting         | Login throttle (config `beha.login_throttle`), API throttle middleware        |
| Database transactions | All workflow transitions + multi-step creations wrapped in `DB::transaction` |
| Soft deletes          | Used on every business entity; never physically deleted in normal flow         |
| Caching               | Redis — `Cache::remember` for org-tree, reference sequences, evaluation templates |
| Queue                 | Redis — notifications, exports, performance recalculation, audit flush       |
| i18n                   | `lang/en/*.php`; structure ready for additional locales                      |
| Error handling        | Custom exception classes per domain; JSON errors for API; Ignition in dev     |

---

## 5. Authentication & Session Architecture

```text
Browser ─────POST /login────▶ AuthController
                                 │
                                 ├─ Rate-limit check (TrackLoginAttempts middleware)
                                 ├─ Verify credentials
                                 ├─ Load roles + permissions (cached)
                                 ├─ Issue session cookie (encrypted)
                                 └─ If `must_change_password = true` → redirect to /password/force-change
Mobile  ─────POST /api/v1/auth/token (Sanctum)──▶ AuthTokenController
                                 │
                                 ├─ Verify credentials
                                 ├─ Issue plain-text API token (shown once)
                                 └─ Token abilities = role-scoped permissions
```

Tokens carry abilities aligned with `permissions.name`. Every API request passes through:
`auth:sanctum` → `permission:{name}` middleware → Policy.

---

## 6. Service Locator Pattern

Services are bound in `App\Providers\AppServiceProvider` (or domain service providers). Controllers consume them via constructor injection — never `app()->make()` inline.

```php
// app/Actions/Customer/RegisterCustomer.php
public function __construct(
    private CustomerReferenceGenerator $refs,
    private Workflow\WorkflowEngine $workflows,
    private Audit\AuditLogger $audit,
) {}
```

---

## 7. Trait Reuse

| Trait                  | Purpose                                                       |
|------------------------|---------------------------------------------------------------|
| `Workflowable`         | Adds `workflowInstances()` relation + `startWorkflow()` helper |
| `Auditable`            | Auto-logs create/update/delete via activitylog                |
| `HasConfidentialId`    | Encrypts confidential_id at attribute access                  |
| `BelongsToOrganization`| Provides `generation`, `branch`, `team` relations             |
| `HasStatusHistory`     | Polymorphic status transitions record                        |

---

## 8. Configuration vs Code Boundary

> **Rule:** any value that differs between deployments or could change as business policy evolves MUST live in `config/beha.php` (backed by `.env`) or in a database table — never inline in code.

| Concern                    | Stored in                          |
|----------------------------|------------------------------------|
| ID prefixes / formats      | `config/beha.php`                  |
| Org capacity limits        | `config/beha.php` + `system_settings` |
| Evaluation criteria        | `evaluation_templates` table       |
| Workflow definitions       | `config/workflows.php` (declared) + `workflow_definitions` table (instance state) |
| Password policy            | `config/beha.php` + `system_settings` |
| Notification routing       | `notification_templates` table      |

---

## 9. Deployment Topology (target)

```text
                     ┌────────────────┐
   Internet ───────▶ │ Nginx (TLS)    │
                     └───────┬────────┘
                             │
                     ┌───────▼────────┐
                     │ PHP-FPM 8.2+   │  ← Laravel app
                     └───┬────────┬───┘
                         │        │
                  ┌──────▼──┐  ┌──▼──────┐
                  │ MySQL 8 │  │  Redis  │
                  └─────────┘  └─────────┘
                         │
                  ┌──────▼──────────┐
                  │ Queue worker(s) │  ← php artisan queue:work
                  └─────────────────┘
                  ┌──────────────────┐
                  │ Scheduler (cron) │  ← php artisan schedule:run
                  └──────────────────┘
                  ┌──────────────────┐
                  │ Storage (S3)     │  ← property images, documents
                  └──────────────────┘
```

All secrets (DB password, mail creds, app key, S3 keys) live in environment variables, never in the repo.

---

## 10. Non-Functional Requirements

| Attribute            | Strategy                                                                |
|----------------------|-------------------------------------------------------------------------|
| Security             | Defense in depth — middleware + policies + DB constraints + encryption  |
| Auditability         | Every sensitive action recorded with user, IP, user-agent, before/after |
| Performance          | Eager-loaded relations, paginated lists, Redis cache, indexed columns    |
| Scalability          | Stateless app servers + Redis-backed sessions + queue workers            |
| Maintainability      | Modular architecture — new workflows added by config + trait            |
| Extensibility        | Each new form = new workflow definition + new model + policy + form request |
| Testability          | Services injected via constructor → mockable; Pest feature tests per workflow |
| Observability        | Laravel logs + Ignition + audit log; ready for Sentry / equivalent      |

---

**Status:** Phase 1 — design approved for the foundation. Subsequent phases implement each module per the roadmap in `docs/08-development-roadmap.md`.
