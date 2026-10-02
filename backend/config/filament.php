<?php

/**
 * Beha — Filament 3 admin panel configuration.
 * Used for sys-admin-only management interfaces (RBAC, settings, audit).
 * Public-facing dashboards still use Blade + Livewire.
 */
$discoverPanels = in_array(env('APP_ENV', 'production'), ['local', 'staging'], true);

return [
    'panel' => [
        'id' => 'admin',
        'path' => 'admin',
        'login' => true,
        'colors' => [
            'primary' => '#0F3A5F',
            'secondary' => '#C8A24B',
        ],
        'discover_resources' => $discoverPanels,
        'discover_pages' => $discoverPanels,
        'discover_widgets' => $discoverPanels,
        'middleware' => [
            'web',
            \App\Http\Middleware\RequireRole::class . ':system_administrator',
        ],
    ],
];
