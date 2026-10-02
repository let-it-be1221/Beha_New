<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * RouteServiceProvider — explicitly loads route files with custom middleware groups.
 *
 * Laravel 11+'s `withRouting()` in bootstrap/app.php handles the default
 * loading, but we still want this provider to register the `web` middleware
 * group label for the SPA cookie auth endpoints (auth/login, auth/logout).
 */
class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        // Routes are loaded via bootstrap/app.php → withRouting().
        // This provider is kept for HOME constant + future custom route bindings.
    }
}
