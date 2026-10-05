# Beha Mock Backend (Testing Tool)

A Node.js + Express + SQLite mock that mirrors the Laravel API routes + workflow logic.
Used for testing the React frontend without needing PHP/MySQL installed.

## When to use this

- When you want to test the frontend UI but don't have XAMPP/PHP set up
- When you want to test workflow logic quickly (faster than Laravel boot)
- When you want to reproduce a bug without touching the real database

## NOT for production

This is a **testing tool only**. It does NOT implement:
- Real RBAC enforcement (all authenticated users can do everything)
- Real audit logging (simplified)
- Real workflow engine (state machine is simplified)
- Real confidential ID encryption
- Real notifications (no-op)

Always use the real Laravel backend for production.

## Quick start

```bash
cd tools/mock-backend
npm install
npm start
# → Mock backend running at http://localhost:8000
```

Then point your frontend at it:

```bash
cd ../../frontend
echo 'VITE_API_URL=http://localhost:8000/api/v1' > .env.local
npm run dev
```

Open http://localhost:5173/login and use any demo credential (password: `Demo1234!`):
- admin@beha.local
- executive@beha.local
- record@beha.local
- finance@beha.local
- genleader@beha.local
- branchleader@beha.local
- teamleader@beha.local
- teammember@beha.local

## What's seeded

- 8 demo users (one per role)
- 1 generation → 1 branch → 1 team
- 6 customers (3 pending team eval, 1 pending record approval, 3 registered with ref codes)
- 4 properties (1 pending verification, 1 pending asset coding, 2 published)
- 4 applicants (1 submitted, 1 team screening, 1 branch assignment, 1 record verification)
- ID sequences (official_id, cus:2026, ast:2026, app:2026)

## Validated workflows

All 3 core workflows have been tested end-to-end against this mock:

1. **Customer**: team_member creates → team_leader evaluates → record_officer approves → ref code issued ✅
2. **Property**: gen_leader creates → exec verifies → record_officer assigns asset code → publishes ✅  
3. **Applicant**: public apply → team_leader screens → branch_leader assigns → record_officer generates IDs → sys_admin creates account ✅
