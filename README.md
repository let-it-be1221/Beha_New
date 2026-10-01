# Beha — Real Estate Management System

A production-grade **Real Estate Management, Sales, Customer, Property, Asset, Employee Hierarchy, Evaluation, Finance, and Workflow Management System** built on Laravel.

> **Status**: Phase 1 deliverable — Architecture, ERD, RBAC, Workflow Design + Laravel foundation scaffold (models, migrations, services, RBAC, ID generation, workflow engine skeleton). See [`docs/`](docs/) for full design documentation.

---

## Tech Stack

| Layer            | Choice                                              |
|------------------|-----------------------------------------------------|
| Backend          | Laravel 11 (PHP 8.2+)                              |
| Database         | MySQL 8+                                            |
| Frontend         | Blade + Livewire 3                                  |
| UI Framework     | Tailwind CSS 3 + Filament 3 components              |
| Auth             | Laravel Auth + Sanctum (API tokens)                 |
| Authorization    | Spatie Laravel Permission (RBAC) + Laravel Policies |
| Cache / Queue    | Redis                                               |
| File Storage     | Laravel Storage (local / S3-ready)                  |
| Audit            | spatie/laravel-activitylog + custom AuditLog model  |
| Media            | spatie/laravel-medialibrary                         |
| Exports          | maatwebsite/excel + barryvdh/laravel-dompdf         |
| Testing          | Pest 2 / 3                                          |

---

## Project Structure

```
beha/
├── app/
│   ├── Actions/                 # Single-purpose action classes
│   ├── Console/                 # Console commands (cron, schedulers)
│   ├── Enums/                   # Native enums (UserLevel, CustomerStatus, ...)
│   ├── Events/                  # Domain events
│   ├── Exceptions/              # Custom exception classes
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/            # Form requests (validation + auth)
│   │   └── Resources/            # API transformers
│   ├── Jobs/                    # Queue jobs (notifications, exports, scoring)
│   ├── Listeners/               # Event listeners
│   ├── Models/                  # Eloquent models
│   ├── Notifications/           # Email / DB / broadcast notifications
│   ├── Policies/                # Per-model authorization policies
│   ├── Repositories/            # Query / persistence repositories
│   ├── Services/
│   │   ├── ID/                  # Official ID, Confidential ID, Reference, Asset Code
│   │   ├── Evaluation/         # Evaluation engine (criteria, scoring, promotion)
│   │   ├── Workflow/            # Workflow engine (steps, transitions, approvals)
│   │   ├── Performance/         # Performance scoring + ranking
│   │   ├── Customer/            # Customer duplicate detection + lifecycle
│   │   ├── Property/            # Property verification + asset coding + publish
│   │   └── Organization/        # Hierarchy enforcement + capacity validation
│   ├── Support/                 # Helpers, formatters
│   ├── Traits/                  # Workflowable, Auditable, HasConfidentialId
│   └── View/Components/         # Blade components (status badges, breadcrumbs, ...)
├── bootstrap/
├── config/
├── database/
│   ├── migrations/             # All schema migrations (numbered by phase)
│   ├── seeders/                # Roles, permissions, default admin, settings
│   └── factories/              # Test data factories
├── docs/                       # Phase 1 design documents (see below)
├── lang/                       # i18n strings
├── public/
├── resources/
│   ├── css/                    # Tailwind entry
│   ├── js/                     # Alpine + Chart.js + app entry
│   └── views/                  # Blade layouts, components, dashboards
├── routes/
│   ├── web.php                 # Web routes (Blade + Livewire)
│   ├── api.php                 # /api/v1 REST API (mobile-ready)
│   └── channels.php            # Broadcast channels
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
├── package.json
├── README.md                    ← you are here
└── docs/README.md               ← start here for Phase 1 design
```

---

## Phase 1 Design Documents (`docs/`)

| #  | Document                              | Contents                                                                 |
|----|---------------------------------------|--------------------------------------------------------------------------|
| 01 | `01-system-architecture.md`           | Layered architecture diagram, request flow, module map                  |
| 02 | `02-database-erd.md`                  | Full ERD in Mermaid (users, RBAC, org, customers, properties, workflows) |
| 03 | `03-rbac-permission-matrix.md`        | Role × Action matrix (Create/Read/Update/Delete/Approve/Reject/...)    |
| 04 | `04-workflow-diagrams.md`             | 6 workflow state machines (customer, property, applicant, promotion, ID gen, publication) |
| 05 | `05-database-schema.md`              | Complete tables, fields, types, indexes, FKs, constraints              |
| 06 | `06-ui-ux-structure.md`                | Login, dashboard, sidebar, tables, approval screens, org hierarchy     |
| 07 | `07-id-generation-design.md`          | Official ID / Confidential ID / Reference / Asset Code design           |
| 08 | `08-development-roadmap.md`           | 12-phase incremental plan, milestones, deliverables per phase           |

---

## Installation (when ready to run)

> PHP, Composer, MySQL, and Redis are required.

```bash
git clone https://github.com/let-it-be1221/Beha_New.git
cd Beha_New
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

Default admin (created by seeder):
- Email: `admin@beha.local`
- Password: printed once by the `AdminSeeder` to console / logs (one-time, must be changed on first login).

---

## Critical Business Rules (spec §41)

1. Official IDs are unique and immutable.
2. ConfidentialIDs are unique and access-controlled (server-side).
3. Confidential IDs never appear in API responses, logs, or URLs for unauthorized users.
4. Customer reference codes are unique; existing customers retain their original code.
5. Duplicate customers are detected **before** registration.
6. Only approved properties receive asset codes.
7. Only asset-coded properties can be published.
8. Users cannot bypass workflow steps.
9. Promotion requires a configured evaluation process (no hard-coded criteria).
10. Organizational assignments respect hierarchy + capacity.
11. Users cannot approve their own submissions.
12. Every approval/rejection is audited.
13. Temporary passwords must be changed on first login.
14. Temporary passwords are stored hashed, never plaintext.
15. Soft-deleted records preserve audit history.
16. All important workflow transitions are transactional.

---

## Development Methodology

This project follows an **incremental 12-phase plan** (see `docs/08-development-roadmap.md`). Each phase is reviewed and approved before the next begins. New forms and workflows are added via the reusable workflow engine — **do not invent business rules where requirements are not provided** (spec §39).

---

## License

Proprietary — © Beha Real Estate Organization. All rights reserved.
