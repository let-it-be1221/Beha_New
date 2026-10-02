<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Generation;
use App\Models\Property;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Enums\CustomerStatus;
use App\Enums\PropertyStatus;
use App\Enums\ApplicantStatus;
use App\Enums\WorkflowStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DashboardController — role-aware dashboard stats endpoint.
 *
 * Spec §22 — each role gets a tailored dashboard with relevant KPIs and
 * pending-work lists. Single endpoint, branch on the user's primary role.
 *
 * GET /api/v1/dashboard/stats → returns { role, stats, pending, recent_activity }
 */
class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        // Pick the first role for dashboard routing (users with multiple roles
        // get the highest-privilege one for dashboard purposes).
        $role = $this->primaryRole($user);

        return match ($role) {
            'system_administrator' => $this->systemAdminStats($user),
            'executive_officer'     => $this->executiveStats($user),
            'record_officer'        => $this->recordOfficerStats($user),
            'finance_officer'        => $this->financeStats($user),
            'generation_leader'      => $this->generationLeaderStats($user),
            'branch_leader'          => $this->branchLeaderStats($user),
            'team_leader'            => $this->teamLeaderStats($user),
            'team_member'            => $this->teamMemberStats($user),
            default                  => $this->guestStats($user),
        };
    }

    private function primaryRole(User $user): ?string
    {
        $priority = [
            'system_administrator',
            'executive_officer',
            'record_officer',
            'finance_officer',
            'generation_leader',
            'branch_leader',
            'team_leader',
            'team_member',
            'applicant',
        ];
        $userRoles = $user->roles->pluck('name')->all();
        foreach ($priority as $role) {
            if (in_array($role, $userRoles, true)) return $role;
        }
        return null;
    }

    // ─── Role-specific stats ────────────────────────────────────────────────

    private function systemAdminStats(User $user): array
    {
        return [
            'role'         => 'system_administrator',
            'role_label'   => 'System Administrator',
            'stats' => [
                ['label' => 'Total Users',           'value' => User::count(), 'icon' => 'users'],
                ['label' => 'Active Users',          'value' => User::active()->count(), 'icon' => 'user-check'],
                ['label' => 'Pending Applications', 'value' => Applicant::where('status', ApplicantStatus::Submitted)->count(), 'icon' => 'clipboard-list'],
                ['label' => 'Generations',          'value' => Generation::count(), 'icon' => 'network'],
                ['label' => 'Branches',              'value' => DB::table('branches')->count(), 'icon' => 'git-branch'],
                ['label' => 'Teams',                  'value' => DB::table('teams')->count(), 'icon' => 'users-2'],
                ['label' => 'Customers',              'value' => Customer::count(), 'icon' => 'contact'],
                ['label' => 'Properties',             'value' => Property::count(), 'icon' => 'building'],
                ['label' => 'Pending Workflows',      'value' => WorkflowInstance::whereNull('finalized_at')->count(), 'icon' => 'workflow'],
                ['label' => 'Audit Alerts (24h)',    'value' => AuditLog::where('severity', 'warning')->where('created_at', '>=', now()->subDay())->count(), 'icon' => 'alert-triangle'],
            ],
            'pending' => [
                'type' => 'workflows',
                'items' => WorkflowInstance::with(['subject', 'currentOwner'])
                    ->whereNull('finalized_at')
                    ->latest()->limit(8)->get()
                    ->map(fn($w) => $this->formatWorkflowItem($w)),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function executiveStats(User $user): array
    {
        return [
            'role'         => 'executive_officer',
            'role_label'   => 'Executive Officer',
            'stats' => [
                ['label' => 'Properties Awaiting Verification', 'value' => Property::where('status', PropertyStatus::PendingVerification)->count(), 'icon' => 'building'],
                ['label' => 'Approved Properties',              'value' => Property::whereIn('status', [PropertyStatus::Verified, PropertyStatus::PendingAssetCoding, PropertyStatus::Published])->count(), 'icon' => 'check-circle'],
                ['label' => 'Published Properties',              'value' => Property::published()->count(), 'icon' => 'globe'],
                ['label' => 'Total Customers',                  'value' => Customer::count(), 'icon' => 'users'],
                ['label' => 'Registered Customers',              'value' => Customer::where('status', CustomerStatus::Registered)->count(), 'icon' => 'user-check'],
                ['label' => 'Total Generations',                 'value' => Generation::count(), 'icon' => 'network'],
                ['label' => 'Pending Approvals (Executive)',     'value' => WorkflowInstance::where('workflow_type', 'property_registration')->whereNull('finalized_at')->count(), 'icon' => 'clipboard-list'],
                ['label' => 'Audit Events (24h)',                'value' => AuditLog::where('created_at', '>=', now()->subDay())->count(), 'icon' => 'activity'],
            ],
            'pending' => [
                'type' => 'properties_to_verify',
                'items' => Property::where('status', PropertyStatus::PendingVerification)
                    ->with('registeredBy')->latest()->limit(8)->get()
                    ->map(fn($p) => [
                        'id' => $p->id,
                        'title' => $p->name,
                        'subtitle' => $p->city ?? '—',
                        'meta' => 'Registered by ' . ($p->registeredBy?->username ?? '—'),
                        'route' => "/properties/{$p->id}",
                    ]),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function recordOfficerStats(User $user): array
    {
        return [
            'role'         => 'record_officer',
            'role_label'   => 'Record Officer',
            'stats' => [
                ['label' => 'Customers Awaiting Approval', 'value' => Customer::where('status', CustomerStatus::PendingRecordApproval)->count(), 'icon' => 'user-clock'],
                ['label' => 'New Customers Today',          'value' => Customer::whereDate('created_at', today())->count(), 'icon' => 'user-plus'],
                ['label' => 'Duplicate Candidates',         'value' => DB::table('customer_duplicates')->where('resolved', false)->count(), 'icon' => 'copy'],
                ['label' => 'Properties Awaiting Asset Coding', 'value' => Property::where('status', PropertyStatus::PendingAssetCoding)->count(), 'icon' => 'building'],
                ['label' => 'Pending ID Assignments',       'value' => Applicant::where('status', ApplicantStatus::RecordVerification)->count(), 'icon' => 'id-card'],
                ['label' => 'Reference Codes Issued (YTD)', 'value' => DB::table('customer_references')->whereYear('issued_at', now()->year)->count(), 'icon' => 'hash'],
                ['label' => 'Asset Codes Issued (YTD)',     'value' => DB::table('property_assets')->whereYear('assigned_at', now()->year)->count(), 'icon' => 'qr-code'],
                ['label' => 'Total Published Properties',    'value' => Property::published()->count(), 'icon' => 'globe'],
            ],
            'pending' => [
                'type' => 'customers_to_approve',
                'items' => Customer::where('status', CustomerStatus::PendingRecordApproval)
                    ->with('salesAgent')->latest()->limit(8)->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'title' => $c->full_name,
                        'subtitle' => $c->reference_code,
                        'meta' => 'Phone: ' . $c->phone,
                        'route' => "/customers/{$c->id}",
                    ]),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function financeStats(User $user): array
    {
        $totalSales = Property::where('status', PropertyStatus::Published)->sum('price');
        $totalCustomers = Customer::where('status', CustomerStatus::Registered)->count();

        return [
            'role'         => 'finance_officer',
            'role_label'   => 'Finance Officer',
            'stats' => [
                ['label' => 'Total Registered Customers', 'value' => $totalCustomers, 'icon' => 'users'],
                ['label' => 'Published Properties',       'value' => Property::published()->count(), 'icon' => 'building'],
                ['label' => 'Total Property Value (ETB)', 'value' => (int) $totalSales, 'icon' => 'dollar-sign', 'is_currency' => true],
                ['label' => 'Pending Workflows',          'value' => WorkflowInstance::whereNull('finalized_at')->count(), 'icon' => 'clipboard-list'],
                ['label' => 'Audit Events (24h)',         'value' => AuditLog::where('created_at', '>=', now()->subDay())->count(), 'icon' => 'activity'],
            ],
            'pending' => [
                'type' => 'workflows',
                'items' => WorkflowInstance::with(['subject', 'currentOwner'])
                    ->whereNull('finalized_at')
                    ->latest()->limit(8)->get()
                    ->map(fn($w) => $this->formatWorkflowItem($w)),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function generationLeaderStats(User $user): array
    {
        $teamMember = $user->teamMember;
        $generationId = $teamMember?->team?->branch?->generation_id;

        $branches = DB::table('branches')->when($generationId, fn($q) => $q->where('generation_id', $generationId))->count();
        $teams    = DB::table('teams')->whereIn('branch_id', function ($q) use ($generationId) {
            $q->select('id')->from('branches')->when($generationId, fn($q) => $q->where('generation_id', $generationId));
        })->count();
        $members = TeamMember::when($generationId, function ($q) use ($generationId) {
            $q->whereHas('team.branch', fn($b) => $b->where('generation_id', $generationId));
        })->count();

        return [
            'role'         => 'generation_leader',
            'role_label'   => 'Generation Leader',
            'stats' => [
                ['label' => 'Branches in Generation', 'value' => $branches, 'icon' => 'git-branch'],
                ['label' => 'Teams',                  'value' => $teams, 'icon' => 'users-2'],
                ['label' => 'Team Members',           'value' => $members, 'icon' => 'users'],
                ['label' => 'Customer Registrations', 'value' => Customer::when($generationId, fn($q) => $q->where('generation_id', $generationId))->count(), 'icon' => 'user-plus'],
                ['label' => 'Properties Registered',  'value' => Property::when($generationId, fn($q) => $q->where('generation_id', $generationId))->count(), 'icon' => 'building'],
                ['label' => 'Published Properties',   'value' => Property::published()->count(), 'icon' => 'globe'],
                ['label' => 'Pending Workflows',      'value' => WorkflowInstance::whereNull('finalized_at')->count(), 'icon' => 'clipboard-list'],
            ],
            'pending' => [
                'type' => 'workflows',
                'items' => WorkflowInstance::with(['subject', 'currentOwner'])
                    ->whereNull('finalized_at')
                    ->latest()->limit(8)->get()
                    ->map(fn($w) => $this->formatWorkflowItem($w)),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function branchLeaderStats(User $user): array
    {
        $teamMember = $user->teamMember;
        $branchId = $teamMember?->team?->branch_id;

        $teams = DB::table('teams')->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();
        $members = TeamMember::when($branchId, fn($q) => $q->whereHas('team', fn($t) => $t->where('branch_id', $branchId)))->count();

        return [
            'role'         => 'branch_leader',
            'role_label'   => 'Branch Leader',
            'stats' => [
                ['label' => 'Teams in Branch',          'value' => $teams, 'icon' => 'users-2'],
                ['label' => 'Team Members',             'value' => $members, 'icon' => 'users'],
                ['label' => 'Pending Applicant Assignments', 'value' => Applicant::where('status', ApplicantStatus::BranchAssignment)->count(), 'icon' => 'clipboard-list'],
                ['label' => 'Customer Registrations', 'value' => Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(), 'icon' => 'user-plus'],
                ['label' => 'Pending Workflows',        'value' => WorkflowInstance::whereNull('finalized_at')->count(), 'icon' => 'workflow'],
            ],
            'pending' => [
                'type' => 'applicants_to_assign',
                'items' => Applicant::where('status', ApplicantStatus::BranchAssignment)
                    ->latest()->limit(8)->get()
                    ->map(fn($a) => [
                        'id' => $a->id,
                        'title' => $a->full_name,
                        'subtitle' => $a->application_code,
                        'meta' => $a->email,
                        'route' => "/applicants/{$a->id}",
                    ]),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function teamLeaderStats(User $user): array
    {
        $teamMember = $user->teamMember;
        $teamId = $teamMember?->team_id;

        $members = TeamMember::when($teamId, fn($q) => $q->where('team_id', $teamId))->count();
        $pendingCustomerEvals = Customer::when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->where('status', CustomerStatus::PendingTeamEvaluation)->count();
        $pendingScreenings = Applicant::where('status', ApplicantStatus::TeamLeaderScreening)->count();

        return [
            'role'         => 'team_leader',
            'role_label'   => 'Team Leader',
            'stats' => [
                ['label' => 'Team Members',                    'value' => $members, 'icon' => 'users'],
                ['label' => 'Pending Customer Evaluations',    'value' => $pendingCustomerEvals, 'icon' => 'user-clock'],
                ['label' => 'Pending Applicant Screenings',    'value' => $pendingScreenings, 'icon' => 'clipboard-list'],
                ['label' => 'Customers in Pipeline',            'value' => Customer::when($teamId, fn($q) => $q->where('team_id', $teamId))->count(), 'icon' => 'user-plus'],
                ['label' => 'Pending Workflows',                'value' => WorkflowInstance::whereNull('finalized_at')->count(), 'icon' => 'workflow'],
            ],
            'pending' => [
                'type' => 'customers_to_evaluate',
                'items' => Customer::where('status', CustomerStatus::PendingTeamEvaluation)
                    ->when($teamId, fn($q) => $q->where('team_id', $teamId))
                    ->with('salesAgent')->latest()->limit(8)->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'title' => $c->full_name,
                        'subtitle' => $c->reference_code,
                        'meta' => 'Submitted by ' . ($c->salesAgent?->username ?? '—'),
                        'route' => "/customers/{$c->id}",
                    ]),
            ],
            'recent_activity' => AuditLog::with('user')->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function teamMemberStats(User $user): array
    {
        $myCustomers = Customer::where('sales_agent_user_id', $user->id)->count();
        $myPendingCustomers = Customer::where('sales_agent_user_id', $user->id)
            ->whereNotIn('status', [CustomerStatus::Registered, CustomerStatus::Rejected])->count();
        $myRegistered = Customer::where('sales_agent_user_id', $user->id)
            ->where('status', CustomerStatus::Registered)->count();
        $availableProperties = Property::published()->count();

        return [
            'role'         => 'team_member',
            'role_label'   => $user->level === 2 ? 'Senior Team Member' : 'Team Member',
            'stats' => [
                ['label' => 'My Customers (Total)',     'value' => $myCustomers, 'icon' => 'users'],
                ['label' => 'Pending Customers',       'value' => $myPendingCustomers, 'icon' => 'user-clock'],
                ['label' => 'Registered Customers',    'value' => $myRegistered, 'icon' => 'user-check'],
                ['label' => 'Available Properties',    'value' => $availableProperties, 'icon' => 'building'],
                ['label' => 'My Level',                 'value' => $user->level, 'icon' => 'award'],
                ['label' => 'My Role',                  'value' => 'Team Member', 'icon' => 'badge'],
            ],
            'pending' => [
                'type' => 'my_recent_customers',
                'items' => Customer::where('sales_agent_user_id', $user->id)
                    ->latest()->limit(8)->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'title' => $c->full_name,
                        'subtitle' => $c->reference_code,
                        'meta' => $c->status_label ?? $c->status,
                        'route' => "/customers/{$c->id}",
                    ]),
            ],
            'recent_activity' => AuditLog::where('user_id', $user->id)->latest()->limit(10)->get()
                ->map(fn($l) => $this->formatAuditItem($l)),
        ];
    }

    private function guestStats(User $user): array
    {
        return [
            'role'         => 'guest',
            'role_label'   => 'Guest',
            'stats' => [],
            'pending' => ['type' => 'none', 'items' => []],
            'recent_activity' => [],
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function formatWorkflowItem($w): array
    {
        return [
            'id' => $w->id,
            'title' => ucwords(str_replace('_', ' ', $w->workflow_type)),
            'subtitle' => "Step: {$w->current_step}",
            'meta' => 'Assigned to ' . ($w->currentOwner?->username ?? 'unassigned'),
            'route' => "/workflows/{$w->id}",
        ];
    }

    private function formatAuditItem($l): array
    {
        return [
            'id' => $l->id,
            'action' => $l->action,
            'actor' => $l->user?->username ?? 'system',
            'category' => $l->category,
            'severity' => $l->severity,
            'created_at' => $l->created_at?->toIso8601String(),
        ];
    }
}
