<?php

/**
 * Beha — auth configuration.
 * Uses Laravel's default guard with session driver for web + Sanctum for API.
 */

return [
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => null,
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,

    // Custom Beha flags
    'first_login_route' => 'password.force-change',
    'home_route'        => 'dashboard',
];
