<?php

use App\Http\Controllers\Api\Auth\SanctumAuthController;
use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\ApplicantController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

/**
 * Beha Backend — REST API v1.
 *
 * Auth strategy (spec §37):
 *   - SPA uses Sanctum cookie-based session auth (stateful, same-domain)
 *   - Mobile / external clients use Sanctum API tokens (Bearer)
 *
 * Frontend origin (http://localhost:5173 in dev) must be in
 * SANCTUM_STATEFUL_DOMAINS env var. CORS is configured in config/cors.php.
 */

Route::get('/', fn () => response()->json([
    'name'    => 'Beha API',
    'version' => 'v1',
    'time'    => now()->toIso8601String(),
]))->name('api.root');

// ─── Public auth endpoints ─────────────────────────────────────────────
Route::post('/auth/token', [TokenController::class, 'store'])
    ->name('api.auth.token');            // mobile / external API tokens

Route::get('/properties', [\App\Http\Controllers\Api\PublicPropertyController::class, 'index']);
Route::get('/properties/{property}', [\App\Http\Controllers\Api\PublicPropertyController::class, 'show']);

// Public applicant self-signup (spec §14)
Route::post('/applicants/apply', [ApplicantController::class, 'apply']);

// ─── SPA cookie auth (stateful) ─────────────────────────────────────────
// EnsureFrontendRequestsAreStateful applies the web middleware to trusted SPA
// requests; do not wrap these routes in web a second time.
Route::post('/auth/login', [SanctumAuthController::class, 'login'])
    ->name('api.auth.login');

Route::post('/auth/forgot-password', [SanctumAuthController::class, 'sendResetLink'])
    ->middleware('throttle:5,1');
Route::post('/auth/reset-password', [SanctumAuthController::class, 'resetPassword']);

// ─── Authenticated endpoints ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // SPA cookie-based logout
    Route::post('/auth/logout', [SanctumAuthController::class, 'logout'])
        ->name('api.auth.logout');

    // Mobile token revocation
    Route::delete('/auth/token', [TokenController::class, 'destroy'])
        ->name('api.auth.token.destroy');

    // Current user
    Route::get('/auth/me', [SanctumAuthController::class, 'me'])
        ->name('api.auth.me');

    Route::get('/me', [SanctumAuthController::class, 'me'])
        ->name('api.me');

    // Dashboard — role-aware stats endpoint
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->name('api.dashboard.stats');

    // Force password change (spec §19)
    Route::post('/auth/change-password', [SanctumAuthController::class, 'changePassword'])
        ->name('api.auth.change-password');

    Route::get('/notifications', [SanctumAuthController::class, 'notifications']);

    // ─── Resources (full CRUD + workflow actions) ──────────────────────
    Route::apiResource('users', UserController::class);

    Route::apiResource('applicants', ApplicantController::class)->except(['store']);
    Route::post('/applicants/{applicant}/screen',        [ApplicantController::class, 'screen']);
    Route::post('/applicants/{applicant}/assign',         [ApplicantController::class, 'assign']);
    Route::post('/applicants/{applicant}/generate-ids',  [ApplicantController::class, 'generateIds']);
    Route::post('/applicants/{applicant}/create-account',[ApplicantController::class, 'createAccount']);

    Route::apiResource('customers', CustomerController::class);
    Route::post('/customers/{customer}/evaluate', [CustomerController::class, 'evaluate']);
    Route::post('/customers/{customer}/approve',   [CustomerController::class, 'approve']);
    Route::post('/customers/{customer}/reject',    [CustomerController::class, 'reject']);

    Route::apiResource('properties', PropertyController::class);
    Route::post('/properties/{property}/verify',           [PropertyController::class, 'verify']);
    Route::post('/properties/{property}/assign-asset-code',[PropertyController::class, 'assignAssetCode']);
    Route::post('/properties/{property}/publish',          [PropertyController::class, 'publish']);

    Route::apiResource('evaluations', EvaluationController::class);

    // Organization
    Route::get('/generations',          [OrganizationController::class, 'generations']);
    Route::post('/generations',         [OrganizationController::class, 'storeGeneration']);
    Route::get('/generations/{id}',     [OrganizationController::class, 'generation']);
    Route::put('/generations/{id}',    [OrganizationController::class, 'updateGeneration']);
    Route::delete('/generations/{id}', [OrganizationController::class, 'destroyGeneration']);

    Route::get('/branches',             [OrganizationController::class, 'branches']);
    Route::post('/branches',            [OrganizationController::class, 'storeBranch']);
    Route::get('/branches/{id}',        [OrganizationController::class, 'branch']);
    Route::put('/branches/{id}',       [OrganizationController::class, 'updateBranch']);
    Route::delete('/branches/{id}',    [OrganizationController::class, 'destroyBranch']);

    Route::get('/teams',                [OrganizationController::class, 'teams']);
    Route::post('/teams',              [OrganizationController::class, 'storeTeam']);
    Route::get('/teams/{id}',           [OrganizationController::class, 'team']);
    Route::put('/teams/{id}',          [OrganizationController::class, 'updateTeam']);
    Route::delete('/teams/{id}',       [OrganizationController::class, 'destroyTeam']);

    Route::get('/organization/tree',    [OrganizationController::class, 'tree']);
    Route::get('/organization/capacity', [OrganizationController::class, 'capacity']);

    // Workflows
    Route::get('/workflows',            [WorkflowController::class, 'index']);
    Route::get('/workflows/{instance}', [WorkflowController::class, 'show']);
    Route::post('/workflows/{instance}/advance', [WorkflowController::class, 'advance']);
    Route::post('/workflows/{instance}/reject',  [WorkflowController::class, 'reject']);
    Route::post('/workflows/{instance}/comment', [WorkflowController::class, 'comment']);

    // Audit logs (sys-admin only)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:audit.read');
});
