<?php

/**
 * Beha Backend — CORS configuration for headless SPA + API tokens.
 *
 * Frontend (http://localhost:5173) must be allowed to send cookies + CSRF.
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'admin/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:3000,http://localhost:8000')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,  // CRITICAL — needed for Sanctum SPA cookies
];
