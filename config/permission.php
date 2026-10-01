<?php

/**
 * Beha — permission configuration (Spatie laravel-permission).
 *
 * Roles & permissions are seeded (see database/seeders/RbacSeeder.php) and
 * cached on first hit. Run `php artisan permission:cache-reset` after seeding.
 */
return [
    'models' => [
        'permission' => App\Models\Permission::class,
        'role'       => App\Models\Role::class,
    ],
    'table_names' => [
        'roles'                 => 'roles',
        'permissions'           => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles'       => 'model_has_roles',
        'role_has_permissions'  => 'role_has_permissions',
    ],
    'column_names' => [
        'model_morph_key' => 'model_id',
    ],
    'display_permission_in_exception' => false,
    'display_role_in_exception'      => false,
    'enable_team_permissions'        => false,
    'teams'                          => false,
    'teams_model'                    => null,
    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key'             => 'spatie.permission.cache',
        'store'           => 'default',
    ],
];
