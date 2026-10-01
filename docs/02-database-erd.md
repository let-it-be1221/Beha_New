# Beha — Phase 1 Document 02: Database ERD

> Full entity-relationship diagram for the Beha system. Spec §42.B.
> Tables are organized by bounded context. See `docs/05-database-schema.md` for the full DDL.

---

## Legend

- `PK` Primary Key
- `FK` Foreign Key
- `UQ` Unique
- `IDX` Indexed
- Soft deletes via `deleted_at TIMESTAMP NULL` on every business table.
- Every table has `created_at`, `updated_at`, and (where applicable) `deleted_at`.

---

## ERD — Complete System (Mermaid)

```mermaid
erDiagram
    users ||--o{ model_has_roles : has
    roles ||--o{ model_has_roles : grants
    roles ||--o{ role_has_permissions : has
    permissions ||--o{ role_has_permissions : grants

    users ||--|| user_profiles : owns
    users ||--o| team_members : "is (if sales)"
    users ||--o{ audit_logs : performs
    users ||--o{ notifications : receives

    generations ||--o{ branches : contains
    branches ||--o{ teams : contains
    teams ||--o{ team_members : contains
    team_members ||--o{ level_promotions : "is promoted"

    applicants ||--o{ applicant_documents : uploads
    applicants ||--o{ applicant_reviews : reviewed
    applicants ||--o{ applicant_assignments : assigned
    applicants ||--|| users : "becomes (after approval)"

    customers ||--o{ customer_documents : has
    customers ||--o{ customer_references : "reference codes (history)"
    customers ||--o{ customer_reviews : reviewed
    customers ||--o{ customer_duplicates : "matches found"

    properties ||--o{ property_documents : has
    properties ||--o{ property_images : has
    properties ||--o{ property_verifications : verified
    properties ||--o| property_assets : "asset code assigned"
    properties ||--o| property_publications : "published to portal"

    evaluation_templates ||--o{ evaluation_criteria : has
    evaluations ||--o{ evaluation_scores : "scored by criteria"
    evaluations }o--|| evaluation_templates : "uses"
    evaluations }o--|| team_members : "subject of"
    evaluations ||--o{ level_promotions : "triggers"

    performance_records ||--|| team_members : "scored"
    performance_rankings ||--|| team_members : "ranked"

    workflow_instances ||--o{ workflow_steps : "has steps"
    workflow_instances ||--o{ workflow_actions : "actions performed"
    workflow_instances ||--o{ workflow_comments : "comments added"
    workflow_instances ||--o{ workflow_attachments : "files attached"
    workflow_instances ||--o{ workflow_history : "transition history"

    system_settings ||--|| id_sequences : "key per sequence"

    users {
        bigint id PK
        string official_id UQ "BH000001 (immutable)"
        string confidential_id_encrypted "NULL unless generated"
        string username UQ
        string email UQ
        string password_hash
        boolean must_change_password
        boolean is_active
        bigint current_team_id FK
        bigint current_role_id FK
        int level "1..5 (only for sales)"
        timestamp email_verified_at
        timestamp last_login_at
        timestamp deleted_at
    }

    generations {
        bigint id PK
        string name UQ
        bigint leader_user_id FK "nullable, Generation Leader"
        int generation_number UQ
        boolean is_active
        timestamp deleted_at
    }

    branches {
        bigint id PK
        bigint generation_id FK
        string name
        bigint leader_user_id FK "nullable, Branch Leader"
        int branch_number
        boolean is_active
        timestamp deleted_at
    }

    teams {
        bigint id PK
        bigint branch_id FK
        string name
        bigint leader_user_id FK "nullable, Team Leader"
        int team_number
        boolean is_active
        timestamp deleted_at
    }

    team_members {
        bigint id PK
        bigint user_id FK UQ
        bigint team_id FK
        int level "1..5"
        date joined_at
        date promoted_at "nullable"
        boolean is_active
        timestamp deleted_at
    }

    level_promotions {
        bigint id PK
        bigint team_member_id FK
        bigint evaluation_id FK "nullable"
        int from_level
        int to_level
        string decision "pass|fail|pending"
        bigint approved_by FK "references users"
        text notes
        timestamp created_at
    }

    applicants {
        bigint id PK
        string application_code UQ "APP-2026-000001"
        string full_name
        string email UQ
        string phone
        string national_id
        text address
        json education
        json experience
        json references
        string status "draft|submitted|under_review|team_screening|branch_assignment|record_verification|account_creation|approved|rejected"
        bigint reviewed_by_team_leader_id FK
        bigint assigned_branch_leader_id FK
        bigint assigned_generation_id FK
        bigint assigned_branch_id FK
        bigint assigned_team_id FK
        bigint record_officer_id FK
        timestamp approved_at
        timestamp rejected_at
        text rejection_reason
        timestamp deleted_at
    }

    customers {
        bigint id PK
        string reference_code UQ "CUS-2026-000001"
        string full_name
        string email
        string phone
        string national_id IDX
        date date_of_birth
        string customer_source
        bigint sales_agent_user_id FK "team member"
        bigint team_id FK
        bigint branch_id FK
        bigint generation_id FK
        string status "draft|submitted|pending_team_evaluation|approved_by_team|pending_record_approval|registered|rejected|correction_required"
        timestamp deleted_at
    }

    properties {
        bigint id PK
        string asset_code UQ "AST-2026-000001 (NULL until asset-coded)"
        string name
        string property_type
        string country
        string region
        string city
        string address
        decimal gps_lat 10,8
        decimal gps_lng 11,8
        decimal size_sqm 10,2
        int number_of_units
        int bedrooms
        int bathrooms
        int floor
        string developer
        string ownership_type
        decimal price 14,2
        string status "draft|pending_verification|verified|pending_asset_coding|published|rejected|correction_required"
        boolean is_published
        timestamp published_at
        bigint registered_by_user_id FK "generation leader"
        timestamp deleted_at
    }

    evaluation_templates {
        bigint id PK
        string code UQ "l1_to_l2, default_member_evaluation, ..."
        string name
        string target_level "1..5|all"
        decimal passing_score 5,2
        boolean is_active
        json metadata
        timestamp deleted_at
    }

    evaluation_criteria {
        bigint id PK
        bigint template_id FK
        string code "sales_performance, attendance, ..."
        string label
        decimal weight 5,2 "percent"
        decimal min_score 5,2
        decimal max_score 5,2
        decimal passing_score 5,2
        json required_evidence
        int sort_order
    }

    evaluations {
        bigint id PK
        bigint template_id FK
        bigint subject_user_id FK "the team member being evaluated"
        bigint evaluator_user_id FK
        bigint team_member_id FK
        decimal total_score 6,2
        decimal weighted_score 6,2
        string decision "pass|fail|pending"
        date evaluation_period_start
        date evaluation_period_end
        text notes
        timestamp finalized_at
        timestamp deleted_at
    }

    workflow_instances {
        bigint id PK
        string workflow_type "customer_registration, property_registration, ..."
        string current_step
        string status "in_progress|approved|rejected|correction_required|cancelled"
        bigint subject_id "polymorphic - customer/property/applicant id"
        string subject_type
        bigint current_owner_user_id FK
        bigint created_by_user_id FK
        timestamp created_at
        timestamp updated_at
        timestamp finalized_at
        timestamp deleted_at
    }

    audit_logs {
        bigint id PK
        bigint user_id FK "nullable, NULL = system"
        string action "login|create|update|delete|approve|reject|promote|..."
        string entity_type "App\\Models\\Customer"
        bigint entity_id
        json old_values
        json new_values
        string ip_address
        string user_agent
        timestamp created_at
    }
```

---

## Bounded Contexts

| Context         | Tables                                                                                         |
|-----------------|------------------------------------------------------------------------------------------------|
| Identity & Auth | users, password_reset_tokens, sessions, personal_access_tokens                                 |
| RBAC            | roles, permissions, model_has_roles, model_has_permissions, role_has_permissions              |
| Organization    | generations, branches, teams, team_members, user_levels, level_promotions                      |
| Identity IDs    | id_sequences, id_generation_history                                                            |
| Applicants      | applicants, applicant_documents, applicant_reviews, applicant_assignments                      |
| Customers       | customers, customer_documents, customer_references, customer_reviews, customer_duplicates    |
| Properties      | properties, property_documents, property_images, property_verifications, property_assets, property_publications |
| Workflows       | workflow_definitions, workflow_instances, workflow_steps, workflow_actions, workflow_comments, workflow_attachments, workflow_history |
| Evaluations     | evaluation_templates, evaluation_criteria, evaluations, evaluation_scores, evaluation_evidence |
| Performance     | performance_records, performance_rankings, performance_ranking_history                        |
| Notifications   | notifications, notification_templates                                                         |
| Audit           | audit_logs, activity_log (spatie)                                                              |
| System          | system_settings, id_sequences, failed_jobs, jobs                                              |

---

## Notes

1. Every business table uses **bigint** unsigned primary keys (Snowflake-safe).
2. Foreign keys are **RESTRICT** on delete — never CASCADE (audit integrity).
3. Soft deletes via `deleted_at TIMESTAMP NULL` — never physically delete in normal flow.
4. Unique constraints on natural keys: `official_id`, `reference_code`, `asset_code`, `application_code`.
5. Confidential IDs are stored **encrypted** (`users.confidential_id_encrypted`), decrypted only at the service layer after a Policy check.
6. Polymorphic relations used by `workflow_instances.subject_*` and `audit_logs.entity_*`.
