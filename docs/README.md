# Beha — Phase 1 Design Documents

> Spec §42 "First Task" deliverables. Review these BEFORE Phase 2 coding begins.

| # | Document | Summary |
|---|----------|---------|
| 01 | [System Architecture](01-system-architecture.md) | Layered architecture, request flow, module map, deployment topology |
| 02 | [Database ERD](02-database-erd.md) | Full entity-relationship diagram in Mermaid |
| 03 | [RBAC Permission Matrix](03-rbac-permission-matrix.md) | Role × Action matrix for every resource |
| 04 | [Workflow Diagrams](04-workflow-diagrams.md) | 6 workflow state machines in Mermaid |
| 05 | [Database Schema](05-database-schema.md) | Complete DDL for every table |
| 06 | [UI/UX Structure](06-ui-ux-structure.md) | Page map, layout, components, design system |
| 07 | [ID Generation Design](07-id-generation-design.md) | Official / Confidential / Customer / Asset ID design |
| 08 | [Development Roadmap](08-development-roadmap.md) | 12-phase plan + branch strategy + release tags |

---

## Reviewer Checklist

- [ ] System architecture reflects the org's actual hierarchy (vertical + horizontal).
- [ ] RBAC matrix covers every workflow action identified in §8–§28.
- [ ] Database schema enforces every business rule from §41 (unique, immutable, FK constraints).
- [ ] Confidential ID protection covers all 6 leak vectors (UI, API, logs, exports, URLs, cache).
- [ ] Workflow engine design handles self-approval prevention, rejection, correction, and rollback.
- [ ] ID generation is configurable (env vars, no hard-coded values).
- [ ] UI/UX structure is responsive (desktop / laptop / tablet / mobile) and role-aware.
- [ ] Roadmap phases map 1:1 to spec §38 phases.

Once approved, proceed to Phase 2 (Laravel Foundation) per `docs/08-development-roadmap.md`.
