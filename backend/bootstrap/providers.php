<?php

/**
 * Beha Backend — Service Provider registration.
 *
 * In Laravel 11+ this file replaces the old config/app.php `providers` array.
 * Bootstrap classes are auto-loaded by the framework; do NOT list them here.
 */

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\RouteServiceProvider::class,
];
