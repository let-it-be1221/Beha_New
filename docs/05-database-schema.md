# Beha — Phase 1 Document 05: Database Schema

> Spec §42.E. Complete MySQL 8+ schema. Migrations live under `database/migrations/` and are numbered by phase.

---

## Conventions

- Engine: InnoDB.
- Charset: `utf8mb4`, collation `utf8mb4_0900_ai_ci`.
- Primary keys: `BIGINT UNSIGNED AUTO_INCREMENT`.
- Foreign keys: `ON DELETE RESTRICT` (audit integrity) unless noted.
- Every business table has `created_at`, `updated_at`, and `deleted_at TIMESTAMP NULL`.
- Polymorphic relations use `subject_type VARCHAR(255)` + `subject_id BIGINT UNSIGNED`.
- JSON columns use MySQL native JSON.
- Encrypted fields use Laravel `encrypt:` cast → stored as `TEXT`.

---

## Table List (grouped)

| # | Group | Table |
|---|-------|-------|
| 1 | Laravel base | users, password_reset_tokens, sessions, cache, jobs, failed_jobs, personal_access_tokens |
| 2 | RBAC (Spatie) | roles, permissions, model_has_roles, model_has_permissions, role_has_permissions |
| 3 | Organization | generations, branches, teams, team_members, user_levels |
| 4 | Identity | id_sequences, id_generation_history |
| 5 | Applicants | applicants, applicant_documents, applicant_reviews, applicant_assignments |
| 6 | Customers | customers, customer_documents, customer_references, customer_reviews, customer_duplicates |
| 7 | Properties | properties, property_documents, property_images, property_verifications, property_assets, property_publications |
| 8 | Workflows | workflow_definitions, workflow_instances, workflow_steps, workflow_actions, workflow_comments, workflow_attachments, workflow_history |
| 9 | Evaluations | evaluation_templates, evaluation_criteria, evaluations, evaluation_scores, evaluation_evidence |
| 10 | Performance | performance_records, performance_rankings, performance_ranking_history |
| 11 | Promotion | level_promotions |
| 12 | Notifications | notifications, notification_templates |
| 13 | Audit | audit_logs |
| 14 | System | system_settings, activity_log (spatie) |

---

## Detailed Schema

### 1. users

```sql
CREATE TABLE users (
  id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  official_id         VARCHAR(32)  NOT NULL UNIQUE,             -- BH000001 (immutable)
  confidential_id     TEXT         NULL,                        -- encrypted at rest
  username            VARCHAR(64)  NOT NULL UNIQUE,
  email               VARCHAR(191) NOT NULL UNIQUE,
  email_verified_at   TIMESTAMP    NULL,
  password            VARCHAR(255) NOT NULL,                     -- bcrypt/argon2id
  must_change_password BOOLEAN      NOT NULL DEFAULT FALSE,
  is_active           BOOLEAN      NOT NULL DEFAULT TRUE,
  failed_login_count  INT          NOT NULL DEFAULT 0,
  locked_until        TIMESTAMP    NULL,
  current_team_id     BIGINT UNSIGNED NULL,
  level               TINYINT      NOT NULL DEFAULT 1,           -- 1..5 (sales only)
  last_login_at       TIMESTAMP    NULL,
  last_login_ip       VARCHAR(45)  NULL,
  remember_token      VARCHAR(100) NULL,
  two_factor_secret   TEXT         NULL,                          -- future
  profile_photo_path  VARCHAR(255) NULL,
  created_at          TIMESTAMP    NULL,
  updated_at          TIMESTAMP    NULL,
  deleted_at          TIMESTAMP    NULL,
  INDEX idx_users_email (email),
  INDEX idx_users_official_id (official_id),
  INDEX idx_users_team (current_team_id),
  INDEX idx_users_level (level),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB;
```

### 2. password_reset_tokens

```sql
CREATE TABLE password_reset_tokens (
  email      VARCHAR(191) NOT NULL PRIMARY KEY,
  token      VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    NULL
) ENGINE=InnoDB;
```

### 3. sessions

```sql
CREATE TABLE sessions (
  id            VARCHAR(191) NOT NULL PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  ip_address    VARCHAR(45)  NULL,
  user_agent    TEXT         NULL,
  payload       LONGTEXT     NOT NULL,
  last_activity INT          NOT NULL,
  INDEX idx_sessions_user (user_id),
  INDEX idx_sessions_last_activity (last_activity)
) ENGINE=InnoDB;
```

### 4. personal_access_tokens (Sanctum)

```sql
CREATE TABLE personal_access_tokens (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id   BIGINT UNSIGNED NOT NULL,
  name           VARCHAR(191) NOT NULL,
  token          VARCHAR(64)  NOT NULL UNIQUE,
  abilities      TEXT         NULL,
  last_used_at   TIMESTAMP    NULL,
  expires_at     TIMESTAMP    NULL,
  created_at     TIMESTAMP    NULL,
  updated_at     TIMESTAMP    NULL,
  INDEX idx_pat_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB;
```

### 5–7. roles / permissions / pivots (Spatie)

```sql
CREATE TABLE roles (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(191) NOT NULL UNIQUE,
  guard_name  VARCHAR(191) NOT NULL DEFAULT 'web',
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(191) NOT NULL UNIQUE,
  guard_name  VARCHAR(191) NOT NULL DEFAULT 'web',
  module      VARCHAR(64)  NOT NULL,  -- 'users', 'customers', 'properties'...
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  INDEX idx_permissions_module (module)
) ENGINE=InnoDB;

CREATE TABLE model_has_permissions (
  permission_id BIGINT UNSIGNED NOT NULL,
  model_type    VARCHAR(191)     NOT NULL,
  model_id      BIGINT UNSIGNED  NOT NULL,
  PRIMARY KEY (permission_id, model_id, model_type),
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  INDEX idx_mhp_model (model_id, model_type)
) ENGINE=InnoDB;

CREATE TABLE model_has_roles (
  role_id    BIGINT UNSIGNED NOT NULL,
  model_type VARCHAR(191)    NOT NULL,
  model_id   BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, model_id, model_type),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  INDEX idx_mhr_model (model_id, model_type)
) ENGINE=InnoDB;

CREATE TABLE role_has_permissions (
  permission_id BIGINT UNSIGNED NOT NULL,
  role_id       BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (permission_id, role_id),
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id)       REFERENCES roles(id)       ON DELETE CASCADE
) ENGINE=InnoDB;
```

### 8. generations

```sql
CREATE TABLE generations (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name             VARCHAR(120) NOT NULL,
  generation_number INT          NOT NULL UNIQUE,
  leader_user_id   BIGINT UNSIGNED NULL,
  description      TEXT         NULL,
  is_active        BOOLEAN      NOT NULL DEFAULT TRUE,
  created_at       TIMESTAMP NULL,
  updated_at       Timestamp NULL,
  deleted_at       TIMESTAMP NULL,
  INDEX idx_gen_leader (leader_user_id),
  INDEX idx_gen_active (is_active)
) ENGINE=InnoDB;
```

### 9. branches

```sql
CREATE TABLE branches (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  generation_id   BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(120) NOT NULL,
  branch_number   INT          NOT NULL,
  leader_user_id  BIGINT UNSIGNED NULL,
  description     TEXT NULL,
  is_active       BOOLEAN NOT NULL DEFAULT TRUE,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  UNIQUE KEY uq_branch_number_per_gen (generation_id, branch_number),
  FOREIGN KEY (generation_id) REFERENCES generations(id),
  INDEX idx_branch_leader (leader_user_id),
  INDEX idx_branch_active (is_active)
) ENGINE=InnoDB;
```

### 10. teams

```sql
CREATE TABLE teams (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  branch_id       BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(120) NOT NULL,
  team_number     INT          NOT NULL,
  leader_user_id  BIGINT UNSIGNED NULL,
  description     TEXT NULL,
  is_active       BOOLEAN NOT NULL DEFAULT TRUE,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  deleted_at      TIMESTAMP NULL,
  UNIQUE KEY uq_team_number_per_branch (branch_id, team_number),
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  INDEX idx_team_leader (leader_user_id),
  INDEX idx_team_active (is_active)
) ENGINE=InnoDB;
```

### 11. team_members

```sql
CREATE TABLE team_members (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  team_id       BIGINT UNSIGNED NOT NULL,
  level         TINYINT NOT NULL DEFAULT 1,           -- 1..5
  joined_at     DATE NOT NULL,
  promoted_at   DATE NULL,
  is_active     BOOLEAN NOT NULL DEFAULT TRUE,
  created_at    TIMESTAMP NULL,
  updated_at    Timestamp NULL,
  deleted_at    TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (team_id) REFERENCES teams(id),
  INDEX idx_tm_team (team_id),
  INDEX idx_tm_level (level),
  INDEX idx_tm_active (is_active)
) ENGINE=InnoDB;
```

### 12. user_levels

```sql
CREATE TABLE user_levels (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code        TINYINT NOT NULL UNIQUE,                -- 1..5
  name        VARCHAR(64) NOT NULL,
  description TEXT NULL,
  is_active   BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
) ENGINE=InnoDB;
```

### 13. id_sequences

```sql
CREATE TABLE id_sequences (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sequence_key  VARCHAR(64)  NOT NULL UNIQUE,         -- 'official_id', 'cus:2026', 'ast:2026'
  next_value    BIGINT       NOT NULL DEFAULT 1,
  prefix        VARCHAR(16)  NULL,
  padding       INT          NOT NULL DEFAULT 6,
  last_used_at  TIMESTAMP    NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL
) ENGINE=InnoDB;
```

### 14. id_generation_history

```sql
CREATE TABLE id_generation_history (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sequence_key    VARCHAR(64) NOT NULL,
  generated_value VARCHAR(64) NOT NULL,
  subject_type    VARCHAR(191) NOT NULL,           -- App\Models\User, etc.
  subject_id      BIGINT UNSIGNED NOT NULL,
  actor_user_id   BIGINT UNSIGNED NULL,
  ip_address      VARCHAR(45)  NULL,
  user_agent      TEXT NULL,
  payload         JSON NULL,
  created_at      TIMESTAMP NULL,
  INDEX idx_idgh_subject (subject_type, subject_id),
  INDEX idx_idgh_value (generated_value),
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

### 15. applicants

```sql
CREATE TABLE applicants (
  id                            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  application_code              VARCHAR(32) NOT NULL UNIQUE,    -- APP-2026-000001
  full_name                     VARCHAR(191) NOT NULL,
  email                         VARCHAR(191) NOT NULL,
  phone                         VARCHAR(32)  NOT NULL,
  national_id                   VARCHAR(64)  NULL,
  address                       TEXT NULL,
  education                     JSON NULL,
  experience                    JSON NULL,
  references                    JSON NULL,
  profile_photo_path            VARCHAR(255) NULL,
  status                        VARCHAR(48)  NOT NULL DEFAULT 'draft',
  reviewed_by_team_leader_id    BIGINT UNSIGNED NULL,
  assigned_branch_leader_id     BIGINT UNSIGNED NULL,
  assigned_generation_id        BIGINT UNSIGNED NULL,
  assigned_branch_id            BIGINT UNSIGNED NULL,
  assigned_team_id              BIGINT UNSIGNED NULL,
  record_officer_id             BIGINT UNSIGNED NULL,
  generated_official_id         VARCHAR(32) NULL,
  generated_confidential_id     TEXT NULL,                       -- encrypted
  approved_at                   TIMESTAMP NULL,
  rejected_at                   TIMESTAMP NULL,
  rejection_reason              TEXT NULL,
  terms_accepted_at             TIMESTAMP NULL,
  created_at                    TIMESTAMP NULL,
  updated_at                    TIMESTAMP NULL,
  deleted_at                    TIMESTAMP NULL,
  INDEX idx_app_status (status),
  INDEX idx_app_email (email),
  INDEX idx_app_assigned_branch (assigned_branch_id),
  INDEX idx_app_assigned_team (assigned_team_id)
) ENGINE=InnoDB;
```

### 16–18. applicant_documents / applicant_reviews / applicant_assignments

```sql
CREATE TABLE applicant_documents (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  applicant_id  BIGINT UNSIGNED NOT NULL,
  document_type VARCHAR(64)  NOT NULL,           -- 'id_card', 'diploma', 'reference_letter', ...
  file_path     VARCHAR(255) NOT NULL,
  file_name     VARCHAR(255) NOT NULL,
  mime_type     VARCHAR(64)  NOT NULL,
  size_bytes    BIGINT       NOT NULL,
  uploaded_by   BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  FOREIGN KEY (applicant_id) REFERENCES applicants(id),
  FOREIGN KEY (uploaded_by)  REFERENCES users(id),
  INDEX idx_ad_applicant (applicant_id),
  INDEX idx_ad_type (document_type)
) ENGINE=InnoDB;

CREATE TABLE applicant_reviews (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  applicant_id  BIGINT UNSIGNED NOT NULL,
  reviewer_user_id BIGINT UNSIGNED NOT NULL,
  review_stage  VARCHAR(48)  NOT NULL,        -- 'team_screening', 'branch_assignment', 'record_verification'
  decision      VARCHAR(24)  NOT NULL,        -- 'approve', 'reject', 'correction_required'
  comment       TEXT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    Timestamp NULL,
  FOREIGN KEY (applicant_id)    REFERENCES applicants(id),
  FOREIGN KEY (reviewer_user_id) REFERENCES users(id),
  INDEX idx_ar_applicant (applicant_id),
  INDEX idx_ar_decision (decision)
) ENGINE=InnoDB;

CREATE TABLE applicant_assignments (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  applicant_id   BIGINT UNSIGNED NOT NULL,
  assigner_user_id BIGINT UNSIGNED NOT NULL,
  generation_id  BIGINT UNSIGNED NULL,
  branch_id      BIGINT UNSIGNED NULL,
  team_id        BIGINT UNSIGNED NULL,
  assigned_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (applicant_id)   REFERENCES applicants(id),
  FOREIGN KEY (assigner_user_id) REFERENCES users(id),
  FOREIGN KEY (generation_id)   REFERENCES generations(id),
  FOREIGN KEY (branch_id)       REFERENCES branches(id),
  FOREIGN KEY (team_id)         REFERENCES teams(id)
) ENGINE=InnoDB;
```

### 19. customers

```sql
CREATE TABLE customers (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  reference_code        VARCHAR(32) NOT NULL UNIQUE,    -- CUS-2026-000001
  full_name             VARCHAR(191) NOT NULL,
  email                 VARCHAR(191) NULL,
  phone                 VARCHAR(32)  NOT NULL,
  national_id           VARCHAR(64)  NULL,
  date_of_birth         DATE NULL,
  customer_source       VARCHAR(64)  NULL,
  sales_agent_user_id   BIGINT UNSIGNED NOT NULL,
  team_id               BIGINT UNSIGNED NOT NULL,
  branch_id             BIGINT UNSIGNED NOT NULL,
  generation_id         BIGINT UNSIGNED NOT NULL,
  status                VARCHAR(48)  NOT NULL DEFAULT 'draft',
  notes                 TEXT NULL,
  metadata              JSON NULL,
  created_at            TIMESTAMP NULL,
  updated_at            Timestamp NULL,
  deleted_at            Timestamp NULL,
  FOREIGN KEY (sales_agent_user_id) REFERENCES users(id),
  FOREIGN KEY (team_id)             REFERENCES teams(id),
  FOREIGN KEY (branch_id)           REFERENCES branches(id),
  FOREIGN KEY (generation_id)       REFERENCES generations(id),
  UNIQUE KEY uq_cust_ref (reference_code),
  INDEX idx_cust_email (email),
  INDEX idx_cust_phone (phone),
  INDEX idx_cust_national (national_id),
  INDEX idx_cust_status (status),
  INDEX idx_cust_team (team_id),
  INDEX idx_cust_branch (branch_id),
  INDEX idx_cust_gen (generation_id)
) ENGINE=InnoDB;
```

### 20–22. customer_documents / customer_references / customer_reviews / customer_duplicates

```sql
CREATE TABLE customer_documents (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  customer_id   BIGINT UNSIGNED NOT NULL,
  document_type VARCHAR(64) NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  file_name     VARCHAR(255) NOT NULL,
  mime_type     VARCHAR(64)  NOT NULL,
  size_bytes    BIGINT       NOT NULL,
  uploaded_by   BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    Timestamp NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (uploaded_by) REFERENCES users(id),
  INDEX idx_cd_customer (customer_id)
) ENGINE=InnoDB;

CREATE TABLE customer_references (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  customer_id    BIGINT UNSIGNED NOT NULL,
  reference_code VARCHAR(32) NOT NULL,
  issued_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  issued_by      BIGINT UNSIGNED NOT NULL,
  note           VARCHAR(255) NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (issued_by)   REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE customer_reviews (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  customer_id   BIGINT UNSIGNED NOT NULL,
  reviewer_user_id BIGINT UNSIGNED NOT NULL,
  review_stage  VARCHAR(48) NOT NULL,    -- 'team_leader', 'record_officer'
  decision      VARCHAR(24) NOT NULL,    -- 'approve', 'reject', 'correction_required'
  comment       TEXT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    Timestamp NULL,
  FOREIGN KEY (customer_id)   REFERENCES customers(id),
  FOREIGN KEY (reviewer_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE customer_duplicates (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  new_customer_id  BIGINT UNSIGNED NOT NULL,
  existing_customer_id BIGINT UNSIGNED NOT NULL,
  match_field      VARCHAR(64) NOT NULL,   -- 'national_id', 'phone', 'email', 'name+dob'
  match_score      DECIMAL(5,2) NOT NULL,
  resolved         BOOLEAN NOT NULL DEFAULT FALSE,
  resolved_by      BIGINT UNSIGNED NULL,
  resolved_action  VARCHAR(24) NULL,       -- 'kept_existing', 'merged', 'created_new'
  created_at       TIMESTAMP NULL,
  updated_at       Timestamp NULL,
  FOREIGN KEY (new_customer_id)      REFERENCES customers(id),
  FOREIGN KEY (existing_customer_id) REFERENCES customers(id),
  FOREIGN KEY (resolved_by)          REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

### 23. properties

```sql
CREATE TABLE properties (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  asset_code            VARCHAR(32) NULL UNIQUE,         -- AST-2026-000001 (NULL until asset-coded)
  name                  VARCHAR(191) NOT NULL,
  property_type         VARCHAR(64)  NOT NULL,
  country               VARCHAR(64)  NULL,
  region                VARCHAR(120) NULL,
  city                  VARCHAR(120) NULL,
  address               VARCHAR(255) NULL,
  gps_lat               DECIMAL(10,8) NULL,
  gps_lng               DECIMAL(11,8) NULL,
  size_sqm              DECIMAL(10,2) NULL,
  number_of_units       INT NOT NULL DEFAULT 1,
  bedrooms              INT NULL,
  bathrooms             INT NULL,
  floor                 INT NULL,
  building_info         VARCHAR(191) NULL,
  developer             VARCHAR(191) NULL,
  ownership_type        VARCHAR(48)  NULL,
  price                 DECIMAL(14,2) NOT NULL,
  description           TEXT NULL,
  amenities             JSON NULL,
  status                VARCHAR(48) NOT NULL DEFAULT 'draft',
  is_published          BOOLEAN NOT NULL DEFAULT FALSE,
  published_at          TIMESTAMP NULL,
  registered_by_user_id BIGINT UNSIGNED NOT NULL,
  generation_id         BIGINT UNSIGNED NULL,
  created_at            TIMESTAMP NULL,
  updated_at            Timestamp NULL,
  deleted_at            Timestamp NULL,
  FOREIGN KEY (registered_by_user_id) REFERENCES users(id),
  FOREIGN KEY (generation_id)         REFERENCES generations(id),
  INDEX idx_prop_status (status),
  INDEX idx_prop_published (is_published),
  INDEX idx_prop_type (property_type),
  INDEX idx_prop_city (city),
  INDEX idx_prop_price (price),
  INDEX idx_prop_registered_by (registered_by_user_id)
) ENGINE=InnoDB;
```

### 24–26. property_documents / property_images / property_verifications / property_assets / property_publications

```sql
CREATE TABLE property_documents (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id   BIGINT UNSIGNED NOT NULL,
  document_type VARCHAR(64) NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  file_name     VARCHAR(255) NOT NULL,
  mime_type     VARCHAR(64)  NOT NULL,
  size_bytes    BIGINT       NOT NULL,
  uploaded_by   BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    Timestamp NULL,
  FOREIGN KEY (property_id) REFERENCES properties(id),
  FOREIGN KEY (uploaded_by) REFERENCES users(id),
  INDEX idx_pd_property (property_id)
) ENGINE=InnoDB;

CREATE TABLE property_images (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id BIGINT UNSIGNED NOT NULL,
  file_path   VARCHAR(255) NOT NULL,
  caption     VARCHAR(255) NULL,
  is_primary  BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NULL,
  updated_at  Timestamp NULL,
  FOREIGN KEY (property_id) REFERENCES properties(id),
  INDEX idx_pi_property (property_id),
  INDEX idx_pi_primary (is_primary)
) ENGINE=InnoDB;

CREATE TABLE property_verifications (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id      BIGINT UNSIGNED NOT NULL,
  verifier_user_id BIGINT UNSIGNED NOT NULL,
  decision         VARCHAR(24) NOT NULL,  -- 'verify', 'reject', 'correction_required'
  comment          TEXT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       Timestamp NULL,
  FOREIGN KEY (property_id)       REFERENCES properties(id),
  FOREIGN KEY (verifier_user_id)  REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE property_assets (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id     BIGINT UNSIGNED NOT NULL UNIQUE,
  asset_code      VARCHAR(32) NOT NULL UNIQUE,
  assigned_by     BIGINT UNSIGNED NOT NULL,
  assigned_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id),
  FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE property_publications (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  property_id  BIGINT UNSIGNED NOT NULL UNIQUE,
  published_by BIGINT UNSIGNED NOT NULL,
  published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  unpublished_by BIGINT UNSIGNED NULL,
  unpublished_at TIMESTAMP NULL,
  FOREIGN KEY (property_id)     REFERENCES properties(id),
  FOREIGN KEY (published_by)    REFERENCES users(id),
  FOREIGN KEY (unpublished_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

### 27–33. workflow_*

```sql
CREATE TABLE workflow_definitions (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code           VARCHAR(64) NOT NULL UNIQUE,        -- 'customer_registration', ...
  label          VARCHAR(191) NOT NULL,
  config         JSON NOT NULL,                      -- mirror of config/workflows.php
  is_active      BOOLEAN NOT NULL DEFAULT TRUE,
  created_at     TIMESTAMP NULL,
  updated_at     Timestamp NULL
) ENGINE=InnoDB;

CREATE TABLE workflow_instances (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  workflow_type         VARCHAR(64) NOT NULL,
  subject_type          VARCHAR(191) NOT NULL,
  subject_id            BIGINT UNSIGNED NOT NULL,
  current_step          VARCHAR(64)  NOT NULL,
  current_owner_user_id BIGINT UNSIGNED NULL,
  created_by_user_id    BIGINT UNSIGNED NOT NULL,
  status                VARCHAR(48) NOT NULL DEFAULT 'in_progress',
  finalized_at          TIMESTAMP NULL,
  created_at            TIMESTAMP NULL,
  updated_at            Timestamp NULL,
  deleted_at            Timestamp NULL,
  FOREIGN KEY (current_owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by_user_id)    REFERENCES users(id),
  INDEX idx_wi_subject (subject_type, subject_id),
  INDEX idx_wi_status (status),
  INDEX idx_wi_owner (current_owner_user_id),
  INDEX idx_wi_type (workflow_type)
) ENGINE=InnoDB;

CREATE TABLE workflow_steps (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  workflow_instance_id BIGINT UNSIGNED NOT NULL,
  step_index      INT NOT NULL,
  step_name       VARCHAR(64) NOT NULL,
  actor_role      VARCHAR(64) NOT NULL,
  expected_action VARCHAR(48) NOT NULL,
  completed_at    TIMESTAMP NULL,
  completed_by    BIGINT UNSIGNED NULL,
  FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
  FOREIGN KEY (completed_by)         REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ws_instance (workflow_instance_id)
) ENGINE=InnoDB;

CREATE TABLE workflow_actions (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  workflow_instance_id BIGINT UNSIGNED NOT NULL,
  actor_user_id        BIGINT UNSIGNED NOT NULL,
  action               VARCHAR(48) NOT NULL,  -- 'submit', 'approve', 'reject', 'forward', 'publish'
  from_step            VARCHAR(64) NULL,
  to_step              VARCHAR(64) NULL,
  comment              TEXT NULL,
  ip_address           VARCHAR(45) NULL,
  user_agent           TEXT NULL,
  created_at           TIMESTAMP NULL,
  FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id)        REFERENCES users(id),
  INDEX idx_wa_instance (workflow_instance_id),
  INDEX idx_wa_actor (actor_user_id)
) ENGINE=InnoDB;

CREATE TABLE workflow_comments (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  workflow_instance_id BIGINT UNSIGNED NOT NULL,
  author_user_id       BIGINT UNSIGNED NOT NULL,
  body                 TEXT NOT NULL,
  created_at           TIMESTAMP NULL,
  updated_at           Timestamp NULL,
  FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
  FOREIGN KEY (author_user_id)       REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE workflow_attachments (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  workflow_instance_id BIGINT UNSIGNED NOT NULL,
  uploaded_by          BIGINT UNSIGNED NOT NULL,
  file_path            VARCHAR(255) NOT NULL,
  file_name            VARCHAR(255) NOT NULL,
  mime_type            VARCHAR(64)  NOT NULL,
  size_bytes           BIGINT       NOT NULL,
  created_at           TIMESTAMP NULL,
  FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by)          REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE workflow_history (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  workflow_instance_id BIGINT UNSIGNED NOT NULL,
  from_step            VARCHAR(64) NULL,
  to_step              VARCHAR(64) NULL,
  actor_user_id       BIGINT UNSIGNED NULL,
  action              VARCHAR(48) NOT NULL,
  metadata            JSON NULL,
  created_at          TIMESTAMP NULL,
  FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id)        REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_wh_instance (workflow_instance_id)
) ENGINE=InnoDB;
```

### 34–36. evaluation_templates / evaluation_criteria / evaluations / evaluation_scores / evaluation_evidence

```sql
CREATE TABLE evaluation_templates (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code           VARCHAR(64) NOT NULL UNIQUE,        -- 'l1_to_l2', 'default_member_evaluation'
  name           VARCHAR(191) NOT NULL,
  target_level   TINYINT NULL,                       -- 1..5 (NULL = any)
  passing_score  DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  is_active      BOOLEAN NOT NULL DEFAULT TRUE,
  metadata       JSON NULL,
  created_at     TIMESTAMP NULL,
  updated_at     Timestamp NULL,
  deleted_at     Timestamp NULL,
  INDEX idx_et_target_level (target_level)
) ENGINE=InnoDB;

CREATE TABLE evaluation_criteria (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  template_id     BIGINT UNSIGNED NOT NULL,
  code            VARCHAR(64) NOT NULL,
  label           VARCHAR(191) NOT NULL,
  weight          DECIMAL(5,2) NOT NULL,             -- percent
  min_score       DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  max_score       DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  passing_score   DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  required_evidence JSON NULL,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NULL,
  updated_at      Timestamp NULL,
  FOREIGN KEY (template_id) REFERENCES evaluation_templates(id),
  UNIQUE KEY uq_ec_template_code (template_id, code)
) ENGINE=InnoDB;

CREATE TABLE evaluations (
  id                    BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  template_id           BIGINT UNSIGNED NOT NULL,
  team_member_id        BIGINT UNSIGNED NOT NULL,
  subject_user_id       BIGINT UNSIGNED NOT NULL,
  evaluator_user_id    BIGINT UNSIGNED NOT NULL,
  total_score           DECIMAL(6,2) NOT NULL DEFAULT 0,
  weighted_score        DECIMAL(6,2) NOT NULL DEFAULT 0,
  decision              VARCHAR(24) NOT NULL DEFAULT 'pending',
  evaluation_period_start DATE NOT NULL,
  evaluation_period_end   DATE NOT NULL,
  notes                 TEXT NULL,
  finalized_at          TIMESTAMP NULL,
  created_at            TIMESTAMP NULL,
  updated_at            Timestamp NULL,
  deleted_at            Timestamp NULL,
  FOREIGN KEY (template_id)        REFERENCES evaluation_templates(id),
  FOREIGN KEY (team_member_id)      REFERENCES team_members(id),
  FOREIGN KEY (subject_user_id)     REFERENCES users(id),
  FOREIGN KEY (evaluator_user_id)  REFERENCES users(id),
  INDEX idx_eval_subject (subject_user_id),
  INDEX idx_eval_evaluator (evaluator_user_id),
  INDEX idx_eval_template (template_id),
  INDEX idx_eval_decision (decision)
) ENGINE=InnoDB;

CREATE TABLE evaluation_scores (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  evaluation_id   BIGINT UNSIGNED NOT NULL,
  criterion_id    BIGINT UNSIGNED NOT NULL,
  score           DECIMAL(6,2) NOT NULL,
  comment         TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      Timestamp NULL,
  FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
  FOREIGN KEY (criterion_id)  REFERENCES evaluation_criteria(id),
  UNIQUE KEY uq_es_eval_criterion (evaluation_id, criterion_id)
) ENGINE=InnoDB;

CREATE TABLE evaluation_evidence (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  evaluation_id  BIGINT UNSIGNED NOT NULL,
  criterion_id   BIGINT UNSIGNED NOT NULL,
  file_path      VARCHAR(255) NOT NULL,
  file_name      VARCHAR(255) NOT NULL,
  mime_type      VARCHAR(64) NOT NULL,
  size_bytes     BIGINT NOT NULL,
  uploaded_by    BIGINT UNSIGNED NOT NULL,
  created_at     TIMESTAMP NULL,
  FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
  FOREIGN KEY (criterion_id)  REFERENCES evaluation_criteria(id),
  FOREIGN KEY (uploaded_by)   REFERENCES users(id)
) ENGINE=InnoDB;
```

### 37. level_promotions

```sql
CREATE TABLE level_promotions (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  team_member_id  BIGINT UNSIGNED NOT NULL,
  evaluation_id   BIGINT UNSIGNED NULL,
  from_level      TINYINT NOT NULL,
  to_level        TINYINT NOT NULL,
  decision        VARCHAR(24) NOT NULL,    -- 'pass', 'fail', 'pending'
  approved_by     BIGINT UNSIGNED NULL,
  notes           TEXT NULL,
  created_at      TIMESTAMP NULL,
  FOREIGN KEY (team_member_id) REFERENCES team_members(id),
  FOREIGN KEY (evaluation_id)  REFERENCES evaluations(id) ON DELETE SET NULL,
  FOREIGN KEY (approved_by)    REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_lp_member (team_member_id),
  INDEX idx_lp_decision (decision)
) ENGINE=InnoDB;
```

### 38–40. performance_records / performance_rankings / performance_ranking_history

```sql
CREATE TABLE performance_records (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  team_member_id BIGINT UNSIGNED NOT NULL,
  period_start   DATE NOT NULL,
  period_end     DATE NOT NULL,
  metric         VARCHAR(64) NOT NULL,   -- 'sales_total', 'customers_registered', 'attendance_rate'
  value          DECIMAL(12,2) NOT NULL,
  recorded_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (team_member_id) REFERENCES team_members(id),
  INDEX idx_pr_member (team_member_id),
  INDEX idx_pr_metric (metric),
  INDEX idx_pr_period (period_start, period_end)
) ENGINE=InnoDB;

CREATE TABLE performance_rankings (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  team_member_id BIGINT UNSIGNED NOT NULL UNIQUE,
  rank_in_team   INT NULL,
  rank_in_branch INT NULL,
  rank_in_generation INT NULL,
  overall_score  DECIMAL(8,2) NOT NULL DEFAULT 0,
  recalculated_at TIMESTAMP NULL,
  FOREIGN KEY (team_member_id) REFERENCES team_members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE performance_ranking_history (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  team_member_id BIGINT UNSIGNED NOT NULL,
  rank_in_team   INT NULL,
  rank_in_branch INT NULL,
  rank_in_generation INT NULL,
  overall_score  DECIMAL(8,2) NOT NULL,
  reason         VARCHAR(64) NULL,        -- 'sales_updated', 'evaluation_finalized', 'nightly_batch'
  recorded_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (team_member_id) REFERENCES team_members(id) ON DELETE CASCADE,
  INDEX idx_prh_member (team_member_id),
  INDEX idx_prh_recorded (recorded_at)
) ENGINE=InnoDB;
```

### 41–42. notifications / notification_templates

```sql
-- notifications table is created by Laravel default migration (database.notifications)

CREATE TABLE notification_templates (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code         VARCHAR(64) NOT NULL UNIQUE,
  channel      VARCHAR(32) NOT NULL,        -- 'database', 'email', 'sms', 'push'
  subject      VARCHAR(191) NULL,
  body         TEXT NOT NULL,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE,
  variables    JSON NULL,                  -- {placeholders}
  created_at   TIMESTAMP NULL,
  updated_at   Timestamp NULL
) ENGINE=InnoDB;
```

### 43. audit_logs

```sql
CREATE TABLE audit_logs (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NULL,
  action        VARCHAR(64) NOT NULL,     -- 'login', 'create', 'update', 'approve', 'promote', 'reject'
  entity_type   VARCHAR(191) NULL,
  entity_id     BIGINT UNSIGNED NULL,
  old_values    JSON NULL,
  new_values    JSON NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    TEXT NULL,
  category      VARCHAR(48) NULL,        -- 'auth', 'rbac', 'workflow', 'security'
  severity      VARCHAR(16) NOT NULL DEFAULT 'info',  -- 'info', 'warning', 'critical'
  created_at    TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_action (action),
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_category (category),
  INDEX idx_audit_severity (severity),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;
```

### 44. system_settings

```sql
CREATE TABLE system_settings (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `key`       VARCHAR(191) NOT NULL UNIQUE,
  value       TEXT NULL,
  type        VARCHAR(24) NOT NULL DEFAULT 'string',  -- 'string', 'int', 'bool', 'json'
  description TEXT NULL,
  is_encrypted BOOLEAN NOT NULL DEFAULT FALSE,
  updated_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NULL,
  updated_at  Timestamp NULL,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## Indexing Strategy

| Pattern | Index |
|---------|-------|
| FK columns | Always indexed |
| Soft delete filter | Composite `(deleted_at, status)` |
| Status enums | Indexed |
| Reference codes | `UNIQUE` |
| Created_at | Indexed on audit_logs, workflow_actions, notifications |
| Composite for org scoping | `(generation_id, branch_id, team_id)` on customers, properties |

---

## Migrations to Generate

Each table above maps 1:1 to a numbered migration in `database/migrations/`:

```
2025_10_01_000001_create_users_table.php
2025_10_01_000002_create_password_reset_tokens_table.php
2025_10_01_000003_create_sessions_table.php
2025_10_01_000004_create_personal_access_tokens_table.php
2025_10_01_000010_create_rbac_tables.php
2025_10_01_000020_create_generations_table.php
2025_10_01_000021_create_branches_table.php
2025_10_01_000022_create_teams_table.php
2025_10_01_000023_create_team_members_table.php
2025_10_01_000024_create_user_levels_table.php
2025_10_01_000030_create_id_sequences_table.php
2025_10_01_000031_create_id_generation_history_table.php
2025_10_01_000040_create_applicants_table.php
2025_10_01_000041_create_applicant_documents_table.php
2025_10_01_000042_create_applicant_reviews_table.php
2025_10_01_000043_create_applicant_assignments_table.php
2025_10_01_000050_create_customers_table.php
2025_10_01_000051_create_customer_documents_table.php
2025_10_01_000052_create_customer_references_table.php
2025_10_01_000053_create_customer_reviews_table.php
2025_10_01_000054_create_customer_duplicates_table.php
2025_10_01_000060_create_properties_table.php
2025_10_01_000061_create_property_documents_table.php
2025_10_01_000062_create_property_images_table.php
2025_10_01_000063_create_property_verifications_table.php
2025_10_01_000064_create_property_assets_table.php
2025_10_01_000065_create_property_publications_table.php
2025_10_01_000070_create_workflow_definitions_table.php
2025_10_01_000071_create_workflow_instances_table.php
2025_10_01_000072_create_workflow_steps_table.php
2025_10_01_000073_create_workflow_actions_table.php
2025_10_01_000074_create_workflow_comments_table.php
2025_10_01_000075_create_workflow_attachments_table.php
2025_10_01_000076_create_workflow_history_table.php
2025_10_01_000080_create_evaluation_templates_table.php
2025_10_01_000081_create_evaluation_criteria_table.php
2025_10_01_000082_create_evaluations_table.php
2025_10_01_000083_create_evaluation_scores_table.php
2025_10_01_000084_create_evaluation_evidence_table.php
2025_10_01_000090_create_level_promotions_table.php
2025_10_01_000100_create_performance_records_table.php
2025_10_01_000101_create_performance_rankings_table.php
2025_10_01_000102_create_performance_ranking_history_table.php
2025_10_01_000110_create_notifications_table.php
2025_10_01_000111_create_notification_templates_table.php
2025_10_01_000120_create_audit_logs_table.php
2025_10_01_000130_create_system_settings_table.php
```
