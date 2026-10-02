/**
 * Beha Frontend — Type definitions matching backend Laravel models.
 * Mirror the API Resources in backend/app/Http/Resources/.
 */

export type Role =
  | 'system_administrator'
  | 'finance_officer'
  | 'executive_officer'
  | 'record_officer'
  | 'generation_leader'
  | 'branch_leader'
  | 'team_leader'
  | 'team_member'
  | 'applicant';

export type UserLevel = 1 | 2 | 3 | 4 | 5;

export interface User {
  id: number;
  official_id: string;
  username: string;
  email: string;
  is_active: boolean;
  level: number;
  roles: Role[];
  permissions: string[];
  last_login_at: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  // Confidential ID is OMITTED in API responses unless viewer is authorized.
  confidential_id?: string;
}

export type CustomerStatus =
  | 'draft'
  | 'submitted'
  | 'pending_team_evaluation'
  | 'approved_by_team'
  | 'pending_record_approval'
  | 'registered'
  | 'rejected'
  | 'correction_required';

export interface Customer {
  id: number;
  reference_code: string;
  full_name: string;
  email: string | null;
  phone: string;
  status: CustomerStatus;
  status_label: string;
  customer_source: string | null;
  sales_agent: { id: number; official_id: string; username: string } | null;
  team_id: number;
  branch_id: number;
  generation_id: number;
  created_at: string;
  updated_at: string;
}

export type PropertyStatus =
  | 'draft'
  | 'pending_verification'
  | 'verified'
  | 'pending_asset_coding'
  | 'published'
  | 'rejected'
  | 'correction_required';

export interface Property {
  id: number;
  asset_code: string | null;
  name: string;
  property_type: string;
  city: string | null;
  region: string | null;
  country: string | null;
  price: number;
  bedrooms: number | null;
  bathrooms: number | null;
  size_sqm: number | null;
  status: PropertyStatus;
  status_label: string;
  is_published: boolean;
  published_at: string | null;
  primary_image: string | null;
  amenities: string[] | null;
  registered_by: { id: number; official_id: string; username: string } | null;
  created_at: string;
}

export type ApplicantStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'team_leader_screening'
  | 'branch_assignment'
  | 'record_verification'
  | 'account_creation'
  | 'approved'
  | 'rejected';

export interface Applicant {
  id: number;
  application_code: string;
  full_name: string;
  email: string;
  phone: string;
  status: ApplicantStatus;
  status_label: string;
  assigned_generation: { id: number; name: string; generation_number: number } | null;
  assigned_branch: { id: number; name: string; branch_number: number } | null;
  assigned_team: { id: number; name: string; team_number: number } | null;
  approved_at: string | null;
  rejected_at: string | null;
  rejection_reason: string | null;
  official_id: string | null; // only present when viewer is authorized
  created_at: string;
}

export type WorkflowStatus =
  | 'in_progress'
  | 'approved'
  | 'rejected'
  | 'correction_required'
  | 'cancelled';

export interface WorkflowInstance {
  id: number;
  workflow_type: string;
  subject_type: string;
  subject_id: number;
  current_step: string;
  current_owner_user_id: number | null;
  created_by_user_id: number;
  status: WorkflowStatus;
  finalized_at: string | null;
  created_at: string;
  updated_at: string;
  current_owner?: User | null;
  created_by?: User | null;
  actions?: WorkflowAction[];
  comments?: WorkflowComment[];
}

export interface WorkflowAction {
  id: number;
  actor_user_id: number;
  action: string;
  from_step: string | null;
  to_step: string | null;
  comment: string | null;
  ip_address: string | null;
  created_at: string;
}

export interface WorkflowComment {
  id: number;
  author_user_id: number;
  body: string;
  created_at: string;
}

export interface AuditLog {
  id: number;
  user_id: number | null;
  action: string;
  entity_type: string | null;
  entity_id: number | null;
  ip_address: string | null;
  user_agent: string | null;
  category: string | null;
  severity: 'info' | 'warning' | 'critical';
  created_at: string;
  user?: Pick<User, 'id' | 'official_id' | 'username'> | null;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  links?: { url: string | null; label: string; active: boolean }[];
}

export interface Generation {
  id: number;
  name: string;
  generation_number: number;
  is_active: boolean;
  leader_user_id: number | null;
  branches?: Branch[];
}

export interface Branch {
  id: number;
  generation_id: number;
  name: string;
  branch_number: number;
  is_active: boolean;
  leader_user_id: number | null;
  teams?: Team[];
}

export interface Team {
  id: number;
  branch_id: number;
  name: string;
  team_number: number;
  is_active: boolean;
  leader_user_id: number | null;
  members?: TeamMember[];
}

export interface TeamMember {
  id: number;
  user_id: number;
  team_id: number;
  level: number;
  joined_at: string;
  promoted_at: string | null;
  is_active: boolean;
  user?: Pick<User, 'id' | 'official_id' | 'username'>;
}

// ─── Dashboard types ────────────────────────────────────────────────────

export type DashboardRole =
  | 'system_administrator'
  | 'executive_officer'
  | 'record_officer'
  | 'finance_officer'
  | 'generation_leader'
  | 'branch_leader'
  | 'team_leader'
  | 'team_member'
  | 'guest';

export interface DashboardStat {
  label: string;
  value: number | string;
  icon: string;            // lucide icon name (e.g. 'users', 'building')
  is_currency?: boolean;
}

export interface DashboardPendingItem {
  id: number;
  title: string;
  subtitle: string;
  meta: string;
  route: string;
}

export interface DashboardActivityItem {
  id: number;
  action: string;
  actor: string;
  category: string | null;
  severity: 'info' | 'warning' | 'critical';
  created_at: string;
}

export interface DashboardStats {
  role: DashboardRole;
  role_label: string;
  stats: DashboardStat[];
  pending: {
    type: string;
    items: DashboardPendingItem[];
  };
  recent_activity: DashboardActivityItem[];
}
