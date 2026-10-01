<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Beha — Application bootstrap.
 *
 * Uses Laravel 11 single-file bootstrap. Middleware aliases, groups, and
 * middleware stack are declared here rather than in the legacy Kernel class.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aliases — referenced by name in routes / controllers
        $middleware->alias([
            'role'       => \App\Http\Middleware\RequireRole::class,
            'permission'=> \App\Http\Middleware\RequirePermission::class,
            'level'      => \App\Http\Middleware\RequireLevel::class,
            'confidential' => \App\Http\Middleware\RequireConfidentialAccess::class,
            'audit'      => \App\Http\Middleware\AuditSensitiveAction::class,
            'first.login' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        // Web group middleware (in order)
        $middleware->web(append: [
            \App\Http\Middleware\TrackLoginAttempts::class,
        ]);

        // API group middleware
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Trust all proxies in production via config (set in TrustProxies middleware if needed)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
