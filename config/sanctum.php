<?php

/**
 * Beha — Sanctum configuration for stateful SPA + mobile API tokens.
 */
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:8000,127.0.0.1,127.0.0.1:8000')),
    'guard'    => ['web'],
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'beha_'),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'encrypt_cookies'      => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token'   => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
