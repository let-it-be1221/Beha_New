<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * RbacSeeder — seeds all 9 roles + every permission defined in the matrix
 * (docs/03-rbac-permission-matrix.md).
 *
 * Run `php artisan permission:cache-reset` after this seeder.
 */
class RbacSeeder extends Seeder
{
    /**
     * Permission catalog: [module => [actions...]]
     */
    private array $catalog = [
        'users'        => ['create', 'read', 'update', 'delete', 'assign_role', 'view_confidential'],
        'roles'        => ['create', 'read', 'update', 'delete'],
        'permissions'  => ['read', 'update'],
        'generations'  => ['create', 'read', 'update', 'delete', 'assign_leader'],
        'branches'     => ['create', 'read', 'update', 'delete', 'assign_leader'],
        'teams'        => ['create', 'read', 'update', 'delete', 'assign_leader'],
        'team_members' => ['create', 'read', 'update', 'delete', 'assign', 'promote'],
        'applicants'   => ['read', 'update', 'screen', 'assign_branch', 'generate_ids', 'create_account', 'approve', 'reject'],
        'customers'    => ['read', 'update', 'delete', 'register', 'evaluate', 'verify', 'approve', 'export', 'view_confidential'],
        'properties'   => ['read', 'update', 'delete', 'register', 'verify', 'approve', 'assign_asset_code', 'publish', 'view_confidential'],
        'evaluations'  => ['template_create', 'read', 'conduct', 'approve_promotion'],
        'performance'   => ['read', 'read_reports', 'recalculate'],
        'workflows'     => ['read', 'advance', 'reject'],
        'audit'         => ['read', 'export'],
        'settings'      => ['read', 'update'],
        'notifications' => ['send', 'read'],
        'reports'       => ['generate', 'export'],
    ];

    /**
     * Role → permission map.
     */
    private array $roleMap = [
        'system_administrator' => [
            'users.*', 'roles.*', 'permissions.*',
            'generations.*', 'branches.*', 'teams.*', 'team_members.*',
            'applicants.create_account', 'applicants.approve', 'applicants.reject', 'applicants.read',
            'customers.read', 'customers.export', 'customers.view_confidential',
            'properties.read', 'properties.view_confidential',
            'evaluations.*', 'performance.*', 'workflows.*',
            'audit.read', 'audit.export', 'settings.*', 'notifications.send',
            'reports.*',
        ],
        'finance_officer' => [
            'users.read', 'users.view_confidential',
            'customers.read', 'customers.export', 'customers.view_confidential',
            'properties.read', 'properties.view_confidential',
            'evaluations.read', 'performance.read_reports',
            'workflows.read', 'audit.read', 'settings.read', 'notifications.read',
            'reports.generate', 'reports.export',
        ],
        'executive_officer' => [
            'users.read', 'users.view_confidential',
            'generations.read', 'branches.read', 'teams.read', 'team_members.read',
            'applicants.read', 'applicants.approve', 'applicants.reject',
            'customers.read', 'customers.view_confidential',
            'properties.read', 'properties.verify', 'properties.approve', 'properties.view_confidential',
            'evaluations.read', 'performance.read_reports',
            'workflows.read', 'workflows.advance', 'workflows.reject',
            'audit.read', 'settings.read', 'notifications.send', 'reports.*',
        ],
        'record_officer' => [
            'users.view_confidential',
            'applicants.read', 'applicants.generate_ids',
            'customers.read', 'customers.verify', 'customers.approve', 'customers.view_confidential',
            'properties.read', 'properties.assign_asset_code', 'properties.publish', 'properties.view_confidential',
            'evaluations.read', 'performance.read_reports',
            'workflows.read', 'workflows.advance', 'workflows.reject',
            'audit.read', 'settings.read', 'reports.generate',
        ],
        'generation_leader' => [
            'generations.read', 'branches.read', 'teams.read', 'team_members.read',
            'applicants.read',
            'customers.read',
            'properties.read', 'properties.register',
            'evaluations.read', 'performance.read',
            'workflows.read', 'workflows.advance', 'workflows.reject',
            'notifications.read', 'reports.generate', 'reports.export',
        ],
        'branch_leader' => [
            'branches.read', 'teams.read', 'team_members.read', 'team_members.create',
            'applicants.read', 'applicants.assign_branch', 'applicants.reject',
            'customers.read',
            'properties.read',
            'evaluations.read', 'evaluations.approve_promotion', 'performance.read',
            'workflows.read', 'workflows.advance', 'workflows.reject',
            'notifications.read', 'reports.generate',
        ],
        'team_leader' => [
            'teams.read', 'team_members.read', 'team_members.create',
            'applicants.read', 'applicants.screen', 'applicants.reject',
            'customers.read', 'customers.evaluate',
            'properties.read',
            'evaluations.read', 'evaluations.conduct', 'performance.read',
            'workflows.read', 'workflows.advance', 'workflows.reject',
            'notifications.read', 'reports.generate',
        ],
        'team_member' => [
            'customers.read', 'customers.register',
            'properties.read',
            'evaluations.read', 'performance.read',
            'workflows.read', 'workflows.advance',
            'notifications.read',
        ],
        'applicant' => [
            // self-signup only — no permissions needed
        ],
    ];

    public function run(): void
    {
        // 1. Create all permissions
        $permissionIds = [];
        foreach ($this->catalog as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                $perm = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    [
                        'module' => $module,
                        'display_name' => ucwords(str_replace('_', ' ', $action)) . ' ' . ucfirst($module),
                    ],
                );
                $permissionIds[$name] = $perm->id;
            }
        }

        // 2. Create roles + assign permissions via wildcard match
        foreach ($this->roleMap as $roleSlug => $patterns) {
            $role = Role::firstOrCreate(
                ['name' => $roleSlug, 'guard_name' => 'web'],
                ['display_name' => ucwords(str_replace('_', ' ', $roleSlug))],
            );

            $permissionNames = [];
            foreach ($patterns as $pattern) {
                if (str_contains($pattern, '*')) {
                    [$module] = explode('.', $pattern);
                    foreach ($this->catalog[$module] ?? [] as $action) {
                        $permissionNames[] = "{$module}.{$action}";
                    }
                } else {
                    $permissionNames[] = $pattern;
                }
            }
            $role->syncPermissions(array_unique($permissionNames));
        }

        $this->command->info('RBAC seeded: '
            . count($permissionIds) . ' permissions, '
            . count($this->roleMap) . ' roles.');
    }
}
