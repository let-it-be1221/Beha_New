# Beha — Phase 1 Document 03: RBAC Permission Matrix

> Spec §21 + §42.C. This matrix lists every role, the actions they can take, and the resources they can act on. **All permissions are enforced server-side** (Policies + middleware) — frontend hiding is purely cosmetic.

---

## Roles

| Role ID (slug)        | Display Name            | Type       | Level | View Confidential? |
|-----------------------|-------------------------|------------|-------|--------------------|
| `system_administrator`| System Administrator    | Horizontal | —     | ✅                 |
| `finance_officer`     | Finance Officer         | Horizontal | —     | ✅                 |
| `executive_officer`   | Executive Officer       | Horizontal | —     | ✅                 |
| `record_officer`      | Record Officer          | Horizontal | —     | ✅                 |
| `generation_leader`   | Generation Leader       | Vertical   | 5     | ❌ (own team only) |
| `branch_leader`       | Branch Leader           | Vertical   | 4     | ❌ (own team only) |
| `team_leader`         | Team Leader             | Vertical   | 3     | ❌                 |
| `team_member`         | Team Member             | Vertical   | 1–2   | ❌                 |
| `applicant`           | Applicant (pre-account) | —          | —     | ❌                 |

> A user holds exactly one horizontal role (or none, if pure sales) and one vertical role with a level. Authorization is the intersection of both.

---

## Actions Tracked

`create · read · update · delete · approve · reject · assign · evaluate · publish · export · view_confidential`

---

## Permission Matrix

| Resource              | Action              | sys_admin | finance | executive | record | gen_leader | br_leader | tm_leader | tm_member | applicant |
|-----------------------|---------------------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Users**             | create              | ✅ | — | — | — | — | — | — | — | (self-signup only) |
|                       | read                | ✅ | ✅* | ✅* | ✅* | ✅(own gen) | ✅(own branch) | ✅(own team) | ✅(self) | ✅(self) |
|                       | update              | ✅ | — | — | — | — | — | — | — | — |
|                       | delete (soft)       | ✅ | — | — | — | — | — | — | — | — |
|                       | assign role         | ✅ | — | — | — | — | — | — | — | — |
|                       | view_confidential   | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — |
| **Roles & Permissions** | create/read/update| ✅ | — | — | — | — | — | — | — | — |
| **Generations**       | create              | ✅ | — | ✅(approve) | — | — | — | — | — | — |
|                       | read                | ✅ | ✅ | ✅ | ✅ | ✅(own) | ✅(own branch→gen) | ✅(own team→gen) | ✅(own) | — |
|                       | update              | ✅ | — | — | — | — | — | — | — | — |
| **Branches**          | create              | ✅ | — | — | — | ✅(assigned) | — | — | — | — |
|                       | read                | ✅ | ✅ | ✅ | ✅ | ✅(own) | ✅(own) | ✅(own team→branch) | ✅(own) | — |
|                       | assign leader       | ✅ | — | — | — | ✅ | — | — | — | — |
| **Teams**             | create              | ✅ | — | — | — | — | ✅(own branch) | — | — | — |
|                       | read                | ✅ | ✅ | ✅ | ✅ | ✅(own gen) | ✅(own) | ✅(own) | ✅(own) | — |
|                       | assign leader       | ✅ | — | — | — | — | ✅ | — | — | — |
| **Team Members**      | create / assign     | ✅ | — | — | — | — | ✅ | ✅(own team) | — | — |
|                       | promote             | ✅ | — | — | — | — | ✅ | ✅ | — | — |
| **Applicants**        | self-signup         | — | — | — | — | — | — | — | — | ✅ |
|                       | screen              | — | — | — | — | — | — | ✅ | — | — |
|                       | assign branch       | — | — | — | — | — | ✅ | — | — | — |
|                       | generate IDs        | — | — | — | ✅ | — | — | — | — | — |
|                       | create account      | ✅ | — | — | — | — | — | — | — | — |
|                       | approve             | ✅ | — | ✅ | — | — | — | — | — | — |
|                       | reject              | ✅ | — | ✅ | ✅ | — | ✅ | ✅ | ✅ | — |
| **Customers**         | register            | — | — | — | — | — | — | — | ✅ | — |
|                       | evaluate (team)     | — | — | — | — | — | — | ✅(own team) | — | — |
|                       | verify (record)     | — | — | — | ✅ | — | — | — | — | — |
|                       | approve             | — | — | — | ✅ | — | — | — | — | — |
|                       | detect duplicates   | — | — | — | ✅ | — | — | — | — | — |
|                       | read                | ✅ | ✅* | ✅* | ✅ | ✅(own gen) | ✅(own branch) | ✅(own team) | ✅(own) | — |
|                       | export              | ✅ | ✅ | ✅ | ✅ | ✅(scoped) | ✅(scoped) | — | — | — |
|                       | view_confidential   | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — |
| **Properties**        | register            | — | — | — | — | ✅ | — | — | — | — |
|                       | verify              | — | — | ✅ | — | — | — | — | — | — |
|                       | approve             | — | — | ✅ | — | — | — | — | — | — |
|                       | assign asset code   | — | — | — | ✅ | — | — | — | — | — |
|                       | publish             | — | — | — | ✅ | — | — | — | — | — |
|                       | read (internal)     | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
|                       | read (public portal)| (anyone) | — | — | — | — | — | — | — | ✅(guest) |
|                       | view_confidential   | ✅ | ✅ | ✅ | ✅ | — | — | — | — | — |
| **Evaluations**       | create template     | ✅ | — | — | — | — | — | — | — | — |
|                       | conduct evaluation  | — | — | — | — | — | ✅ | ✅(own team) | — | — |
|                       | approve promotion   | — | — | — | — | — | ✅ | ✅ | — | — |
|                       | read                | ✅ | ✅* | ✅* | ✅ | ✅(own gen) | ✅(own branch) | ✅(own team) | ✅(own) | — |
| **Performance**       | read (own)          | ✅ | ✅* | ✅* | ✅ | ✅ | ✅ | ✅ | ✅ | — |
|                       | read (reports)      | ✅ | ✅ | ✅ | ✅ | ✅(scoped) | ✅(scoped) | ✅(scoped) | — | — |
|                       | recalculate         | ✅ | — | — | — | — | — | — | — | — |
| **Workflows**         | read                | ✅ | ✅* | ✅* | ✅ | ✅(scoped) | ✅(scoped) | ✅(scoped) | ✅(own) | — |
|                       | advance (any)       | ✅ | — | ✅ | ✅ | ✅(gen) | ✅(branch) | ✅(team) | ✅(self) | — |
|                       | reject              | ✅ | — | ✅ | ✅ | ✅(gen) | ✅(branch) | ✅(team) | — | — |
| **Notifications**     | read (own)          | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
|                       | send                | ✅ | — | ✅ | ✅ | — | — | — | — | — |
| **Audit Logs**        | read                | ✅ | — | ✅* | ✅* | — | — | — | — | — |
|                       | export              | ✅ | — | — | — | — | — | — | — | — |
| **System Settings**   | read                | ✅ | — | ✅ | ✅ | — | — | — | — | — |
|                       | update              | ✅ | — | — | — | — | — | — | — | — |
| **Reports**           | generate            | ✅ | ✅ | ✅ | ✅ | ✅(scoped) | ✅(scoped) | ✅(scoped) | — | — |
|                       | export              | ✅ | ✅ | ✅ | ✅ | ✅(scoped) | ✅(scoped) | — | — | — |

✅ = explicitly allowed | ✅* = allowed but with field-level restriction (e.g. `view_confidential` stripped) | (scoped) = limited to the user's organizational subtree | — = denied

---

## Permission Naming Convention

Every permission follows `{resource}.{action}` (Spatie convention). Permissions are seeded once and assigned to roles in `RbacSeeder.php`.

| Category | Examples                                                                       |
|----------|--------------------------------------------------------------------------------|
| Users    | `users.create`, `users.read`, `users.update`, `users.delete`, `users.assign_role`, `users.view_confidential` |
| Org      | `generations.create`, `branches.create`, `teams.create`, `team_members.assign`, `team_members.promote` |
| Customers| `customers.register`, `customers.evaluate`, `customers.verify`, `customers.approve`, `customers.view_confidential`, `customers.export` |
| Properties | `properties.register`, `properties.verify`, `properties.approve`, `properties.assign_asset_code`, `properties.publish`, `properties.view_confidential` |
| Eval     | `evaluations.template.create`, `evaluations.conduct`, `evaluations.approve_promotion`, `evaluations.read` |
| Workflow | `workflows.read`, `workflows.advance`, `workflows.reject` |
| Audit    | `audit.read`, `audit.export` |
| System   | `settings.read`, `settings.update`, `notifications.send`, `reports.generate`, `reports.export` |

---

## Enforcement Layers (Defense in Depth)

1. **Database** — unique constraints, FKs, CHECK constraints prevent data corruption.
2. **Form Requests** — input validation (size, type, allowed values) before reaching controller.
3. **Policies** — `CustomerPolicy`, `PropertyPolicy`, etc. Each method (`create`, `update`, `approve`, etc.) is enforced **server-side**.
4. **Middleware** — `role:`, `permission:`, `level:`, `confidential` middleware for route-level guard.
5. **API Resources** — when serializing, **never** append `confidential_id` unless the requesting user's policy authorizes it.

---

## Self-Approval Prevention (spec §41 rule #11)

The `WorkflowService` checks `current_owner_user_id !== $approver->id` before any `approve`/`reject`/`advance` action. A user attempting to approve their own submission triggers a 403 and an audit log entry of category `security.violation`.

---

## Scoped Reads (Vertical Hierarchy)

Vertical roles receive only data from their subtree:

```text
Generation Leader → sees: own generation + its branches + teams + members + their customers + properties + evaluations
Branch Leader     → sees: own branch + its teams + members + their customers + evaluations
Team Leader       → sees: own team + its members + their customers + evaluations
Team Member       → sees: self + own customers + own evaluations + own performance
```

Scoping is enforced via query scopes: `Customer::visibleTo($user)`, `User::inSubtreeOf($user)`, etc. — **never** via frontend hiding.

---

## Horizontal-Role Confidential ID Visibility

Confidential IDs are decrypted in API Resources / Blade views **only if** `$user->can('users.view_confidential')`. This is checked via `App\Policies\UserPolicy::viewConfidential()` — same rule applied to API responses. A ` confidential_id` field is **omitted entirely** (not nulled) for unauthorized users, so it cannot leak through logs, exports, or cached responses.
