<?php

use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\PropertyController as ApiPropertyController;
use App\Http\Controllers\Api\ApplicantController as ApiApplicantController;
use App\Http\Controllers\Api\OrganizationController as ApiOrganizationController;
use App\Http\Controllers\Api\WorkflowController as ApiWorkflowController;
use Illuminate\Support\Facades\Route;

/**
 * Beha — REST API v1 (mobile-ready, Sanctum-protected).
 * Spec §37.
 */

Route::prefix('v1')->group(function () {

    // Public auth
    Route::post('/auth/token', [TokenController::class, 'store'])->name('api.auth.token');

    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [TokenController::class, 'destroy'])->name('api.auth.logout');
        Route::get('/me', [TokenController::class, 'me'])->name('api.me');

        // Users
        Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->only(['index', 'show', 'update']);

        // Organization
        Route::get('/generations', [ApiOrganizationController::class, 'generations']);
        Route::get('/generations/{id}', [ApiOrganizationController::class, 'generation']);
        Route::get('/branches',    [ApiOrganizationController::class, 'branches']);
        Route::get('/teams',       [ApiOrganizationController::class, 'teams']);

        // Applicants
        Route::apiResource('applicants', ApiApplicantController::class);
        Route::post('/applicants/{id}/screen',   [ApiApplicantController::class, 'screen']);
        Route::post('/applicants/{id}/assign',   [ApiApplicantController::class, 'assign']);
        Route::post('/applicants/{id}/generate-ids', [ApiApplicantController::class, 'generateIds']);

        // Customers
        Route::apiResource('customers', ApiCustomerController::class);
        Route::post('/customers/{id}/evaluate', [ApiCustomerController::class, 'evaluate']);
        Route::post('/customers/{id}/approve',   [ApiCustomerController::class, 'approve']);
        Route::post('/customers/{id}/reject',    [ApiCustomerController::class, 'reject']);

        // Properties
        Route::apiResource('properties', ApiPropertyController::class);
        Route::post('/properties/{id}/verify',          [ApiPropertyController::class, 'verify']);
        Route::post('/properties/{id}/assign-asset-code',[ApiPropertyController::class, 'assignAssetCode']);
        Route::post('/properties/{id}/publish',         [ApiPropertyController::class, 'publish']);

        // Evaluations
        Route::apiResource('evaluations', \App\Http\Controllers\Api\EvaluationController::class);

        // Workflows
        Route::get('/workflows',       [ApiWorkflowController::class, 'index']);
        Route::get('/workflows/{id}',  [ApiWorkflowController::class, 'show']);
        Route::post('/workflows/{id}/advance', [ApiWorkflowController::class, 'advance']);
        Route::post('/workflows/{id}/reject',  [ApiWorkflowController::class, 'reject']);

        // Notifications
        Route::get('/notifications', [TokenController::class, 'notifications']);

        // Audit
        Route::get('/audit-logs', \App\Http\Controllers\Api\AuditLogController::class . '@index')
            ->middleware('permission:audit.read');
    });
});
