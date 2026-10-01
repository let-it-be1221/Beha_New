<?php

/**
 * Beha — Filament 3 admin panel configuration.
 * Used for sys-admin-only management interfaces (RBAC, settings, audit).
 * Public-facing dashboards still use Blade + Livewire.
 */
return [
    'panel' => [
        'id' => 'admin',
        'path' => 'admin',
        'login' => true,
        'colors' => [
            'primary' => '#0F3A5F',
            'secondary' => '#C8A24B',
        ],
        'discover_resources' => in_array(app()->environment(), ['local', 'staging']),
        'discover_pages' => in_array(app()->environment(), ['local', 'staging']),
        'discover_widgets' => in_array(app()->environment(), ['local', 'staging']),
        'middleware' => [
            'web',
            \App\Http\Middleware\RequireRole::class . ':system_administrator',
        ],
    ],
];
