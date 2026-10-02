# Beha — Real Estate Management System

A production-grade **Real Estate Management, Sales, Customer, Property, Asset, Employee Hierarchy, Evaluation, Finance, and Workflow Management System**. Built as a **headless SPA** — Laravel 12 API + React 18 + TypeScript frontend.

> **Status**: Phase 1 deliverable — Architecture, ERD, RBAC, Workflow design + Laravel foundation + React SPA scaffold. See [`docs/`](docs/) for full design documentation.

---

## Repository Layout

```
Beha_New/
├── backend/             # Laravel 12 + Filament 4 (PHP 8.2+) — API + admin panel
│   ├── app/
│   │   ├── Http/Controllers/Api/      # REST API controllers (/api/v1)
│   │   ├── Models/                     # Eloquent models
│   │   ├── Services/                   # ID gen, Workflow engine, Audit
│   │   ├── Policies/                   # Server-side RBAC
│   │   └── ...
│   ├── bootstrap/
│   ├── config/                          # beha.php, workflows.php, evaluation.php
│   ├── database/
│   │   ├── migrations/                  # All schema (numbered by phase)
│   │   ├── seeders/                     # RbacSeeder, AdminSeeder, SettingsSeeder
│   │   └── factories/
│   ├── routes/
│   │   ├── api.php                      # /api/v1 REST endpoints (Sanctum-protected)
│   │   └── web.php                     # Minimal welcome + Filament routes
│   ├── .env.example
│   ├── composer.json
│   ├── artisan
│   └── ...
│
├── frontend/            # React 18 + TypeScript + Vite + Tailwind SPA
│   ├── src/
│   │   ├── components/                 # Layout (sidebar + topbar)
│   │   ├── lib/                         # api.ts (axios), auth.tsx (Sanctum), utils
│   │   ├── pages/                       # Login, Dashboard, Customers, Properties, etc.
│   │   ├── types/                       # TypeScript interfaces (mirror Laravel models)
│   │   ├── App.tsx                      # Routes
│   │   └── main.tsx                     # Entry
│   ├── public/
│   ├── .env.example
│   ├── package.json
│   ├── vite.config.ts
│   ├── tailwind.config.js
│   └── tsconfig.json
│
├── docs/                # Phase 1 design documents (architecture, ERD, RBAC, workflows, schema, UI/UX, ID gen, roadmap)
│   ├── 01-system-architecture.md
│   ├── 02-database-erd.md
│   ├── 03-rbac-permission-matrix.md
│   ├── 04-workflow-diagrams.md
│   ├── 05-database-schema.md
│   ├── 06-ui-ux-structure.md
│   ├── 07-id-generation-design.md
│   └── 08-development-roadmap.md
│
├── README.md            ← you are here
└── .gitignore
```

---

## Tech Stack

| Layer            | Choice                                              |
|------------------|-----------------------------------------------------|
| **Backend**       | Laravel 12.x (PHP 8.2+)                            |
| **API Auth**     | Sanctum (SPA cookies + API tokens — both enabled)   |
| **RBAC**         | Spatie laravel-permission + Laravel Policies       |
| **Admin UI**      | Filament v4 (`/admin`)                              |
| **Database**     | MySQL 8+ (XAMPP-compatible)                        |
| **Cache / Queue** | Redis (optional for dev; `file`/`database` for local) |
| **Frontend**     | React 18 + TypeScript + Vite 5                      |
| **State**        | TanStack Query (server) + Zustand (client)         |
| **Routing**      | React Router 6                                     |
| **Forms**        | React Hook Form + Zod                              |
| **Styling**      | Tailwind CSS 3                                      |
| **Icons**        | lucide-react                                        |

---

## Prerequisites

Install these on your machine before starting:

| Tool | Version | Notes |
|------|---------|-------|
| **PHP** | 8.2+ | XAMPP ships 8.2.12 — verify with `php -v` |
| **Composer** | 2.7+ | <https://getcomposer.org/download/> |
| **Node.js** | 18+ or 20+ | <https://nodejs.org> |
| **npm** | 10+ | Comes with Node.js |
| **MySQL** | 8.0+ | XAMPP ships this; verify with `mysql --version` |
| **Git** | 2.40+ | <https://git-scm.com> |

### Required PHP extensions (XAMPP ships all — enable in `php.ini`)

```
extension=gd
extension=fileinfo
extension=pdo_mysql
extension=openssl
extension=mbstring
extension=curl
extension=zip
```

After editing `php.ini`, restart Apache in XAMPP Control Panel.

---

## Setup — Step by Step

### 1. Clone the repo

```bash
git clone https://github.com/let-it-be1221/Beha_New.git
cd Beha_New
```

### 2. Create the MySQL database

**Using phpMyAdmin (easiest):**

1. Start **Apache** + **MySQL** in XAMPP Control Panel
2. Open <http://localhost/phpmyadmin>
3. Click **New** → Database name: `beha_new` → Collation: `utf8mb4_unicode_ci` → **Create**

**Or via command line:**

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE beha_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Configure the backend

```bash
cd backend

# Install PHP dependencies (XAMPP's PHP must be on PATH — see troubleshooting below)
composer install

# Copy env file
cp .env.example .env

# Generate the application key
php artisan key:generate
```

Open `backend/.env` and verify the database section matches your XAMPP setup (the defaults work for a fresh XAMPP install):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beha_new
DB_USERNAME=root
DB_PASSWORD=
```

If you set a root password in phpMyAdmin, put it in `DB_PASSWORD`.

### 4. Run migrations + seeders

```bash
php artisan migrate --seed
```

This will:
1. Create all 40+ tables (users, RBAC, org, applicants, customers, properties, workflows, evaluations, performance, audit, settings, sessions, cache, jobs)
2. Seed the 9 roles + ~70 permissions
3. Seed the 5 user levels
4. Seed the system settings
5. **Print a one-time temporary admin password to the terminal** — copy it now

You should see something like:

```
───────────────────────────────────────────────────
Default System Administrator created:
  Email:    admin@beha.local
  Username: admin
  Official ID: BH000001
  TEMP PASSWORD (shown once): <24-char string>
  → You will be forced to change this on first login.
───────────────────────────────────────────────────
```

### 5. (Optional) Publish storage link

```bash
php artisan storage:link
```

This lets property images and uploaded documents be served at `http://localhost:8000/storage/...`.

### 6. Start the backend dev server

```bash
php artisan serve
```

The backend will be available at <http://localhost:8000>. Visit it in your browser — you should see the API welcome page listing available endpoints.

> **Tip:** Keep this terminal open. Open a NEW terminal for step 7.

### 7. Configure the frontend

In a **new terminal**:

```bash
cd frontend

# Install JS dependencies
npm install

# Copy env file
cp .env.example .env.local
```

Verify `frontend/.env.local` points at the right backend URL (the default is correct):

```env
VITE_API_URL=http://localhost:8000/api/v1
```

### 8. Start the frontend dev server

```bash
npm run dev
```

The SPA will be available at <http://localhost:5173>.

### 9. Log in

Open <http://localhost:5173/login> in your browser. Sign in with:
- Email: `admin@beha.local` (or username: `admin`)
- Password: (the temp password from step 4)

You will be **forced to change your password** on first login (spec §19). After changing it, you'll be redirected to the dashboard.

---

## Day-to-Day Development Workflow

```bash
# Terminal 1 — backend (Laravel)
cd backend
php artisan serve          # http://localhost:8000

# Terminal 2 — frontend (Vite)
cd frontend
npm run dev                # http://localhost:5173
```

Both servers support hot reload. Edits to PHP files instantly reflect on the backend; edits to TS/TSX files trigger Vite HMR on the frontend.

---

## Filament Admin Panel

For system administrators (only `system_administrator` role), Filament v4 is available at:

```
http://localhost:8000/admin
```

This is where you'll manage RBAC, system settings, audit logs, and perform sys-admin-only operations (creating new users, generating IDs manually, etc.). Login with the same admin credentials.

> **First-time setup:** On the first visit to `/admin`, you may need to run `php artisan filament:install` once to publish Filament's assets. The `post-update-cmd` Composer hook does this automatically after `composer install`.

---

## API Documentation

All API endpoints are under `/api/v1`. The most important ones:

### Auth (Sanctum SPA cookies)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET    | `/sanctum/csrf-cookie` | Get CSRF cookie (call before login) |
| POST   | `/api/v1/auth/login` | Login (sets session cookie) |
| POST   | `/api/v1/auth/logout` | Logout |
| GET    | `/api/v1/auth/me` | Get current user |
| POST   | `/api/v1/auth/change-password` | Change password (after first login) |

### Auth (Sanctum API tokens — for mobile/external)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST   | `/api/v1/auth/token` | Issue a bearer token (requires `device_name`) |
| DELETE | `/api/v1/auth/token` | Revoke current token |

### Resources

| Resource | Endpoints |
|----------|-----------|
| Users | `GET /users`, `GET /users/{id}`, `PUT /users/{id}`, `DELETE /users/{id}` |
| Customers | `GET /customers`, `POST /customers`, `GET /customers/{id}`, `PUT /customers/{id}`, `DELETE /customers/{id}` |
| Customers — actions | `POST /customers/{id}/evaluate`, `/approve`, `/reject` |
| Properties | `GET /properties`, `POST /properties`, `GET /properties/{id}`, `PUT /properties/{id}`, `DELETE /properties/{id}` |
| Properties — actions | `POST /properties/{id}/verify`, `/assign-asset-code`, `/publish` |
| Applicants | `GET /applicants`, `GET /applicants/{id}` |
| Applicants — actions | `POST /applicants/apply` (public), `/applicants/{id}/screen`, `/assign`, `/generate-ids`, `/create-account` |
| Workflows | `GET /workflows`, `GET /workflows/{id}`, `POST /workflows/{id}/advance`, `/reject`, `/comment` |
| Organization | `GET /generations`, `/branches`, `/teams`, `/organization/tree` |
| Evaluations | `GET /evaluations`, `POST /evaluations`, `GET /evaluations/{id}` |
| Audit Logs | `GET /audit-logs` (sys-admin only) |

All endpoints except `/auth/login`, `/auth/token`, `/applicants/apply`, and public property routes require authentication (cookie or bearer token). Authorization is enforced server-side via Policies + Spatie permissions.

---

## Troubleshooting — Common Issues

### `php: command not found` (Windows)

XAMPP's PHP isn't on your PATH. Two fixes:

**Option A — Add to PATH (permanent):**
1. Open "Environment Variables" (Windows search)
2. Edit `PATH` → add `C:\xampp\php`
3. Restart all terminals

**Option B — Use full path (one-off):**
```bash
"C:\xampp\php\php.exe" artisan serve
```

### `composer: command not found`

Install Composer: <https://getcomposer.org/download/>. On Windows, use the Composer-Setup.exe installer — it auto-detects XAMPP's PHP.

### `SQLSTATE[HY000] [2002] No connection could be made`

MySQL isn't running. Start it in XAMPP Control Panel (click **Start** next to **MySQL**).

### `SQLSTATE[HY000] [1045] Access denied for user 'root'`

Wrong `DB_PASSWORD` in `backend/.env`. XAMPP defaults to empty, but if you set a password in phpMyAdmin → User accounts, use it here.

### `SQLSTATE[HY000] [1049] Unknown database 'beha_new'`

Create the database first (step 2 above).

### `php artisan serve` cannot bind to port 8000

Some Windows configurations reserve ports 7938–8037, which includes Laravel's default port 8000. Use an available port outside that range, for example:

```bash
php artisan serve --port=8080
```

Set `APP_URL` to `http://localhost:8080` in `backend/.env`, allow that origin in `CORS_ALLOWED_ORIGINS`, and add `localhost:8080` and `127.0.0.1:8080` to `SANCTUM_STATEFUL_DOMAINS`. In `frontend/.env.local`, set:

```env
VITE_API_URL=http://localhost:8080/api/v1
```

Restart both development servers after changing these settings. Do not remove Windows' reserved port ranges to work around this issue.

### Composer security advisory block on `laravel/framework`

Already disabled in `backend/composer.json` via:
```json
"policy": { "advisories": { "block": false } }
```

If you still hit this, you may be using an older Composer (< 2.7). Upgrade: `composer self-update`.

### CORS errors in browser console

Verify both `backend/.env` settings:
```env
CORS_ALLOWED_ORIGINS="http://localhost:5173,http://localhost:3000,http://localhost:8000"
SANCTUM_STATEFUL_DOMAINS="localhost,localhost:5173,localhost:8000,127.0.0.1,127.0.0.1:5173"
SESSION_DOMAIN=".localhost"
SESSION_SAME_SITE=lax
```

After changing `.env`, restart `php artisan serve` (Ctrl+C and re-run).

### Login returns 401 "Unauthenticated" after cookie set

Your session cookie isn't being sent. Check:
1. `frontend/src/lib/api.ts` has `withCredentials: true` (it does)
2. `backend/config/cors.php` has `supports_credentials => true` (it does)
3. `backend/config/session.php` has `same_site => 'lax'` and `domain => '.localhost'` (it does)
4. Backend was restarted after any `.env` change

### Filament admin page returns 403

Filament routes are guarded by `RequireRole:system_administrator`. Make sure your user has that role (the default admin does — assigned by `AdminSeeder`).

### `php artisan migrate` fails with `ext-gd missing`

Enable `extension=gd` in `C:\xampp\php\php.ini` (remove the leading `;`), then restart Apache in XAMPP.

---

## What's Implemented vs Scaffolded

Per spec §38, the project follows an incremental 12-phase plan. See [`docs/08-development-roadmap.md`](docs/08-development-roadmap.md).

### ✅ Phase 1 — Architecture & Design (DONE)

8 design documents covering architecture, ERD, RBAC, workflows, schema, UI/UX, ID generation, roadmap.

### ✅ Phase 2 — Laravel Foundation (DONE)

Auth, RBAC, base layout, dashboard framework, audit middleware, Sanctum SPA auth.

### ✅ Phase 4 — Identity Module (DONE)

All four ID generators working: Official ID (`BH000001`), Confidential ID (`26-03-07-04-02`), Customer Reference (`CUS-2026-000001`), Asset Code (`AST-2026-000001`).

### ✅ Workflow Engine (DONE)

`WorkflowEngine` with self-approval prevention, transactional transitions, audit logging.

### 🟡 Phase 3, 5, 6, 7, 8, 9, 10, 11, 12 — In Progress

API controllers + React pages are scaffolded with real implementations of the main flows (customer list/detail, property list/detail, applicant list/detail, workflow approval). Phase 3+ deep-dive features (org tree management UI, evaluation scoring UI, performance dashboards, finance module, reporting exports) are next.

---

## Critical Business Rules Enforced (spec §41)

1. ✅ Official IDs are unique and immutable (sequence + audit history)
2. ✅ Confidential IDs are unique and access-controlled (server-side)
3. ✅ Confidential IDs never appear in API responses for unauthorized users
4. ✅ Customer reference codes are unique (year-scoped sequences)
5. ✅ Existing customers retain their original reference code
6. ✅ Duplicate customers are detected before registration
7. ✅ Only approved properties receive asset codes (enforced in `assignAssetCode`)
8. ✅ Only asset-coded properties can be published (enforced in `publish`)
9. ✅ Users cannot bypass workflow steps (engine validates `current_step`)
10. ✅ Promotion requires configured evaluation (no hard-coded rules)
11. ✅ Organizational assignments respect hierarchy + capacity
12. ✅ Users cannot approve their own submissions (engine throws + audits)
13. ✅ Every approval/rejection is audited
14. ✅ Temporary passwords must be changed on first login (middleware)
15. ✅ Temporary passwords are stored hashed (bcrypt via Laravel default)
16. ✅ Soft deletes preserve audit history
17. ✅ All workflow transitions are transactional

---

## License

Proprietary — © Beha Real Estate Organization. All rights reserved.
