<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\CmykColor;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\RequireRole;

/**
 * Beha — Filament v4 admin panel.
 *
 * Mounted at /admin. Only `system_administrator` role can access it
 * (enforced by RequireRole middleware on the panel's middleware stack).
 *
 * Spec §22, §21 — used for sys-admin operations: RBAC, settings, audit logs,
 * user management, ID generation, organization configuration.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => CmykColor::fromHex('#0F3A5F'),  // Beha navy
                'secondary' => CmykColor::fromHex('#C8A24B'), // Beha gold
            ])
            ->brandName('Beha Admin')
            ->brandLogo(asset('images/logo.png'))
            ->favicon(asset('favicon.ico'))
            ->discoverResources(in: 'app/Filament/Resources', for: 'App\\Filament\\Resources')
            ->discoverPages(in: 'app/Filament/Pages', for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: 'app/Filament/Widgets', for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentGroup::class,
            ])
            ->authMiddleware([
                RequireRole::class . ':system_administrator',
            ])
            ->plugins([
                // Add Filament plugins here as the admin UI grows.
            ])
            ->viteTheme('resources/css/app.css', 'resources/js/app.js');
    }
}
